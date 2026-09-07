import Foundation

struct Cliente: Codable, Identifiable {
    let id: Int
    let nome: String
    let email: String?
    let telefone: String?
    let documento: String?
}
