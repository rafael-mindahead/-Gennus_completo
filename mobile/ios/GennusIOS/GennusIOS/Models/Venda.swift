import Foundation

struct Venda: Codable, Identifiable {

    let id: Int
    let produtoId: Int
    let produtoNome: String
    let qtd: Double
    let unidade: String
    let valorTotal: Double
    let custoTotal: Double
    let dataVenda: String

    enum CodingKeys: String, CodingKey {
        case id
        case produtoId = "produto_id"
        case produtoNome = "produto_nome"
        case qtd
        case unidade
        case valorTotal = "valor_total"
        case custoTotal = "custo_total"
        case dataVenda = "data_venda"
    }
}
