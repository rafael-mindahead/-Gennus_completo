import Foundation

struct Produto: Codable, Identifiable {
    let id: Int
    let nome: String
    let categoria: String?
    let unidade: String
    let custo: Double
    let preco: Double
    let estoque: Double
}
