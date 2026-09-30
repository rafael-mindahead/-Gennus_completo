import Foundation

struct APIResponse<T: Codable>: Codable {
    let status: String
    let mensagem: String
    let data: T
}

final class APIService {

    static let shared = APIService()

    private let baseURL = "http://127.0.0.1:8080/php"

    private init() {}

    func buscarProdutos() async throws -> [Produto] {

        guard let url = URL(
            string: "\(baseURL)/produto_get.php"
        ) else {
            throw URLError(.badURL)
        }

        let (data, response) = try await URLSession.shared.data(from: url)

        guard let httpResponse = response as? HTTPURLResponse,
              httpResponse.statusCode == 200 else {
            throw URLError(.badServerResponse)
        }

        let resposta = try JSONDecoder().decode(
            APIResponse<[Produto]>.self,
            from: data
        )

        return resposta.data
    }

    func buscarClientes() async throws -> [Cliente] {

        guard let url = URL(
            string: "\(baseURL)/cliente_get.php"
        ) else {
            throw URLError(.badURL)
        }

        let (data, response) = try await URLSession.shared.data(from: url)

        guard let httpResponse = response as? HTTPURLResponse,
              httpResponse.statusCode == 200 else {
            throw URLError(.badServerResponse)
        }

        let resposta = try JSONDecoder().decode(
            APIResponse<[Cliente]>.self,
            from: data
        )

        return resposta.data
    }
    func buscarVendas() async throws -> [Venda] {

        guard let url = URL(
            string: "\(baseURL)/venda_get.php"
        ) else {
            throw URLError(.badURL)
        }

        let (data, response) = try await URLSession.shared.data(from: url)

        guard let httpResponse = response as? HTTPURLResponse,
              httpResponse.statusCode == 200 else {
            throw URLError(.badServerResponse)
        }

        let resposta = try JSONDecoder().decode(
            APIResponse<[Venda]>.self,
            from: data
        )

        return resposta.data
    }
}
