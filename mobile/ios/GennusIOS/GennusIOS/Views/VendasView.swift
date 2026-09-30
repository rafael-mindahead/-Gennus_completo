import SwiftUI

struct VendasView: View {

    @State private var vendas: [Venda] = []
    @State private var carregando = true
    @State private var erro: String?

    var body: some View {

        NavigationStack {

            Group {

                if carregando {

                    ProgressView("Carregando vendas...")

                } else if let erro {

                    ContentUnavailableView(
                        "Erro",
                        systemImage: "exclamationmark.triangle",
                        description: Text(erro)
                    )

                } else {

                    List(vendas) { venda in

                        VStack(
                            alignment: .leading,
                            spacing: 8
                        ) {

                            HStack {

                                Image(
                                    systemName: "cart.fill"
                                )
                                .foregroundStyle(
                                    AppTheme.accent
                                )

                                Text(venda.produtoNome)
                                    .font(.headline)

                                Spacer()
                            }

                            Text(
                                venda.valorTotal,
                                format: .currency(
                                    code: "BRL"
                                )
                            )
                            .font(.title3)
                            .bold()
                            .foregroundStyle(
                                AppTheme.success
                            )

                            HStack {

                                Text(
                                    "\(venda.qtd.formatted()) \(venda.unidade)"
                                )

                                Spacer()

                                Text(venda.dataVenda)
                            }
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        }
                        .padding(.vertical, 6)
                    }
                }
            }
            .navigationTitle("Vendas")
        }
        .task {
            await carregar()
        }
    }

    private func carregar() async {

        do {
            vendas =
                try await APIService.shared.buscarVendas()
        } catch {
            self.erro = error.localizedDescription
        }

        carregando = false
    }
}

#Preview {
    VendasView()
}
