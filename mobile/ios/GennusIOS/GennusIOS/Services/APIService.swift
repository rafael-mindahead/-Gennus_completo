import Foundation

struct APIResponse<T: Decodable>: Decodable {
    let status: String
    let mensagem: String
    let data: T
}

private struct APIErrorResponse: Decodable {
    let status: String
    let mensagem: String
}

private struct APIError: LocalizedError {
    let message: String
    var errorDescription: String? { message }
}

final class APIService {
    static let shared = APIService()

    // localhost aponta para o Mac no simulador. Para um aparelho, use o endereço do servidor.
    private let baseURL = "http://localhost:8080/php"

    private init() {}

    private func buscar<T: Decodable>(_ endpoint: String) async throws -> T {
        guard let url = URL(string: "\(baseURL)/\(endpoint)") else {
            throw URLError(.badURL)
        }
        var request = URLRequest(url: url, cachePolicy: .reloadIgnoringLocalCacheData, timeoutInterval: 20)
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw URLError(.badServerResponse)
        }
        if let error = try? JSONDecoder().decode(APIErrorResponse.self, from: data), error.status != "ok" {
            throw APIError(message: error.mensagem)
        }
        guard (200..<300).contains(http.statusCode) else {
            throw APIError(message: "Servidor retornou HTTP \(http.statusCode).")
        }
        let result = try JSONDecoder().decode(APIResponse<T>.self, from: data)
        guard result.status == "ok" else {
            throw APIError(message: result.mensagem)
        }
        return result.data
    }

    func buscarProdutos() async throws -> [Produto] {
        try await buscar("produto_get.php")
    }

    func buscarClientes() async throws -> [Cliente] {
        try await buscar("cliente_get.php")
    }

    func buscarVendas() async throws -> [Venda] {
        try await buscar("venda_get.php")
    }
}
