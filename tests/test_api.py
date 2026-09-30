"""Integration tests: run ONLY against a disposable local development database."""
import concurrent.futures
import http.cookiejar
import json
import os
import unittest
import urllib.error
import urllib.request
import uuid

BASE = os.environ.get('GENNUS_TEST_BASE_URL', 'http://127.0.0.1:8080').rstrip('/')

@unittest.skipUnless(os.environ.get('GENNUS_TEST_ALLOW_WRITES') == '1', 'Requires an isolated test database and GENNUS_TEST_ALLOW_WRITES=1')
class API(unittest.TestCase):
    def setUp(self):
        self.jar = http.cookiejar.CookieJar()
        self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
        self.created = []
        self.assertEqual(self.call('usuario_login', {'email': 'admin@gennus.local', 'senha': 'admin123'})[0], 200)
    def tearDown(self):
        for entity, record_id in reversed(self.created):
            self.call(f'{entity}_excluir', {}, record_id=record_id)
    def call(self, endpoint, data=None, *, record_id=None, anonymous=False, raw=None):
        url = f'{BASE}/php/{endpoint}.php' + (f'?id={record_id}' if record_id is not None else '')
        payload = raw if raw is not None else (json.dumps(data).encode() if data is not None else None)
        request = urllib.request.Request(url, payload, {'Content-Type': 'application/json'} if payload is not None else {})
        opener = urllib.request.build_opener() if anonymous else self.client
        try:
            response = opener.open(request, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        return response.code, json.loads(response.read())
    def product(self, stock=2, price=3.50):
        name = 'Test-' + uuid.uuid4().hex
        data = {'nome': name, 'categoria': 'Test', 'unidade': 'kg', 'custo': 1.25, 'preco': price, 'estoque': stock}
        self.assertEqual(self.call('produto_novo', data)[0], 200)
        rows = self.call('produto_get')[1]['data']
        item = next(row for row in rows if row['nome'] == name)
        self.created.append(('produto', item['id']))
        return item, data
    def sale(self, product, quantity):
        before = {row['id'] for row in self.call('venda_get')[1]['data']}
        code, result = self.call('venda_novo', {'produto_id': product['id'], 'qtd': quantity, 'valor_total': 0.01, 'custo_total': 999, 'produto_nome': 'forged'})
        self.assertEqual(code, 200, result)
        row = next(row for row in self.call('venda_get')[1]['data'] if row['id'] not in before)
        self.created.append(('venda', row['id']))
        return row
    def test_session_contract_and_real_logout(self):
        code, result = self.call('valida_sessao')
        self.assertEqual(code, 200)
        self.assertIsInstance(result['data'], dict)
        self.assertNotIn('senha_hash', result['data'])
        self.assertEqual(self.call('cliente_logoff')[0], 405)
        self.assertEqual(self.call('cliente_logoff', {})[0], 200)
        self.assertEqual(self.call('valida_sessao')[0], 401)
    def test_registration_validation_and_duplicate(self):
        unique = uuid.uuid4().hex
        data = {'nome': 'Teste', 'sobrenome': 'API', 'email_corporativo': unique + '@gennus.local', 'senha': 'strong123', 'cpf_cnpj': '', 'telefone': ''}
        self.assertEqual(self.call('usuario_novo', {**data, 'senha': 'x'})[0], 400)
        self.assertEqual(self.call('usuario_novo', {**data, 'email_corporativo': 'bad'})[0], 400)
        self.assertEqual(self.call('usuario_novo', data)[0], 200)
        self.assertEqual(self.call('usuario_novo', data)[0], 409)
        self.assertEqual(self.call('usuario_login', {'email': data['email_corporativo'], 'senha': 'wrong'})[0], 401)
        self.assertEqual(self.call('usuario_login', {'email': data['email_corporativo'], 'senha': data['senha']})[0], 200)
    def test_profile_persists_and_preserves_email(self):
        email = uuid.uuid4().hex + '@gennus.local'
        self.call('usuario_novo', {'nome': 'Antes', 'sobrenome': 'API', 'email_corporativo': email, 'senha': 'strong123'})
        self.call('usuario_login', {'email': email, 'senha': 'strong123'})
        self.assertEqual(self.call('usuario_alterar', {'nome': 'Depois', 'sobrenome': 'Silva', 'senha': 'changed123'})[0], 200)
        self.call('cliente_logoff', {})
        self.assertEqual(self.call('usuario_login', {'email': email, 'senha': 'strong123'})[0], 401)
        self.assertEqual(self.call('usuario_login', {'email': email, 'senha': 'changed123'})[0], 200)
        self.assertEqual(self.call('valida_sessao')[1]['data']['nome'], 'Depois')
    def test_crud_numeric_types_validation_and_missing_ids(self):
        product, data = self.product(stock=1.75)
        self.assertIsInstance(product['id'], int)
        self.assertIsInstance(product['estoque'], (int, float))
        for value in [-1, 'abc', 0.00001, 1000000000]:
            self.assertEqual(self.call('produto_novo', {**data, 'estoque': value})[0], 400)
        self.assertEqual(self.call('produto_alterar', data, record_id=product['id'])[0], 200)
        self.assertEqual(self.call('produto_get', record_id=4294967295)[0], 404)
        self.assertEqual(self.call('produto_get', record_id='abc')[0], 400)
        self.assertEqual(self.call('produto_alterar', data, record_id=4294967295)[0], 404)
        self.assertEqual(self.call('produto_novo', data, anonymous=True)[0], 401)
        self.assertEqual(self.call('produto_excluir', record_id=product['id'])[0], 405)
        self.assertEqual(self.call('produto_novo', raw=b'{broken')[0], 400)
    def test_employee_json_and_client_crud(self):
        name = uuid.uuid4().hex
        employee = {'nome': name, 'tipo': 'CLT', 'doc': name[:20], 'salario': 1500.50, 'status': 'Ativo', 'vt': '', 'vr': '', 'extras': '["<img onerror=x>"]'}
        self.assertEqual(self.call('funcionario_novo', {**employee, 'extras': '{bad'})[0], 400)
        self.assertEqual(self.call('funcionario_novo', employee)[0], 200)
        row = next(row for row in self.call('funcionario_get')[1]['data'] if row['nome'] == name)
        self.created.append(('funcionario', row['id']))
        self.assertEqual(json.loads(row['extras']), ['<img onerror=x>'])
        self.assertEqual(row['salario_base'], 1500.50)
        customer = {'nome': name, 'email': name + '@gennus.local', 'telefone': '', 'documento': ''}
        self.assertEqual(self.call('cliente_novo', customer)[0], 200)
        row = next(row for row in self.call('cliente_get')[1]['data'] if row['nome'] == name)
        self.created.append(('cliente', row['id']))
        self.assertEqual(self.call('cliente_alterar', {**customer, 'nome': 'Atualizado'}, record_id=row['id'])[0], 200)
        self.assertEqual(self.call('cliente_get', record_id=row['id'])[1]['data'][0]['nome'], 'Atualizado')
    def test_sales_atomic_stock_and_trusted_totals(self):
        product, _ = self.product(stock=3)
        sale = self.sale(product, 1.5)
        self.assertEqual(sale['valor_total'], 5.25)
        self.assertEqual(sale['produto_nome'], product['nome'])
        self.assertEqual(sale['custo_total'], 1.88)
        self.assertEqual(self.call('venda_novo', {'produto_id': product['id'], 'qtd': 99})[0], 409)
        self.assertEqual(self.call('produto_get', record_id=product['id'])[1]['data'][0]['estoque'], 1.5)
        self.assertEqual(self.call('venda_alterar', {'qtd': 2, 'valor_total': 1}, record_id=sale['id'])[0], 200)
        self.assertEqual(self.call('produto_get', record_id=product['id'])[1]['data'][0]['estoque'], 1)
        self.assertEqual(self.call('venda_excluir', {}, record_id=sale['id'])[0], 200)
        self.assertEqual(self.call('produto_get', record_id=product['id'])[1]['data'][0]['estoque'], 3)
    def test_concurrent_sales_cannot_oversell(self):
        product, _ = self.product(stock=1)
        def sell(_):
            return self.call('venda_novo', {'produto_id': product['id'], 'qtd': 1})[0]
        with concurrent.futures.ThreadPoolExecutor(2) as pool:
            self.assertEqual(sorted(pool.map(sell, range(2))), [200, 409])
        rows = [row for row in self.call('venda_get')[1]['data'] if row['produto_id'] == product['id']]
        self.assertEqual(len(rows), 1)
        self.created.append(('venda', rows[0]['id']))
        self.assertEqual(self.call('produto_get', record_id=product['id'])[1]['data'][0]['estoque'], 0)
    def test_product_deletion_preserves_sale_snapshot(self):
        product, _ = self.product(stock=2)
        sale = self.sale(product, 1)
        self.assertEqual(self.call('produto_excluir', {}, record_id=product['id'])[0], 200)
        self.assertEqual(self.call('venda_get', record_id=sale['id'])[1]['data'][0]['produto_nome'], product['nome'])
        self.assertEqual(self.call('venda_alterar', {'qtd': 2}, record_id=sale['id'])[0], 409)
        self.assertEqual(self.call('venda_excluir', {}, record_id=sale['id'])[0], 200)
        self.assertEqual(self.call('produto_get', record_id=product['id'])[0], 404)

if __name__ == '__main__':
    unittest.main()
