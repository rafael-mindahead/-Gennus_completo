import SwiftUI

struct ProdutosView: View {

    @State private var produtos: [Produto] = []
    @State private var carregando = true
    @State private var erro: String?

    var body: some View {

        NavigationStack {

            Group {

                if carregando {

                    ProgressView("Carregando produtos...")

                } else if let erro {

                    VStack(spacing: 12) {

                        Image(systemName: "exclamationmark.triangle")
                            .font(.largeTitle)

                        Text("Erro")
                            .font(.headline)

                        Text(erro)
                    }
                    .padding()

                } else {

                    List(produtos) { produto in

                        VStack(alignment: .leading, spacing: 5) {

                            Text(produto.nome)
                                .font(.headline)

                            Text(
                                produto.preco,
                                format: .currency(code: "BRL")
                            )
                            .font(.subheadline)

                            Text(
                                "Estoque: \(produto.estoque.formatted()) \(produto.unidade)"
                            )
                            .font(.caption)
                            .foregroundStyle(.secondary)
                            if produto.estoque <= 5 {

                                Label(
                                    "Estoque baixo",
                                    systemImage: "exclamationmark.triangle.fill"
                                )
                                .font(.caption)
                                .foregroundStyle(AppTheme.warning)
                            }
                        }
                    }
                }
            }
            .navigationTitle("Produtos")
        }
        .task {
            await carregarProdutos()
        }
    }

    private func carregarProdutos() async {

        do {
            produtos = try await APIService.shared.buscarProdutos()
        } catch {
            self.erro = error.localizedDescription
        }

        carregando = false
    }
}

#Preview {
    ProdutosView()
}
