import Foundation

struct APIResponse<T: Codable>: Codable {
    let status: String
    let mensagem: String
    let data: T
}

final class APIService {

    static let shared = APIService()

    private init() {}

    func buscarProdutos() async throws -> [Produto] {

        guard let url = URL(
            string: "http://localhost:8080/php/produto_get.php"
        ) else {
            throw URLError(.badURL)
        }

        let (data, response) = try await URLSession.shared.data(from: url)
        if let json = String(data: data, encoding: .utf8) {
            print("RESPOSTA PHP:")
            print(json)
        }

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
}
