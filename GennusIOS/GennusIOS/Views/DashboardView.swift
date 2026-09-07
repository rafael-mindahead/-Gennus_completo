import SwiftUI
import Charts

struct DashboardView: View {

    @State private var totalProdutos = 0
    @State private var totalClientes = 0
    @State private var totalVendas = 0

    @State private var faturamento: Double = 0

    @State private var vendas: [Venda] = []

    @State private var carregando = true

    var body: some View {

        NavigationStack {

            ZStack {

                AppTheme.background
                    .ignoresSafeArea()

                ScrollView {

                    VStack(
                        alignment: .leading,
                        spacing: 20
                    ) {

                        // MARK: - Cabeçalho

                        VStack(
                            alignment: .leading,
                            spacing: 4
                        ) {

                            Text("Gennus")
                                .font(.largeTitle)
                                .bold()
                                .foregroundStyle(
                                    AppTheme.accent
                                )

                            Text("ERP Mobile")
                                .foregroundStyle(.secondary)
                        }


                        // MARK: - Faturamento

                        card(
                            titulo: "Faturamento",
                            valor: faturamento.formatted(
                                .currency(code: "BRL")
                            ),
                            icone: "dollarsign.circle.fill",
                            cor: AppTheme.success
                        )


                        // MARK: - Produtos / Clientes

                        HStack {

                            card(
                                titulo: "Produtos",
                                valor: "\(totalProdutos)",
                                icone: "shippingbox.fill",
                                cor: AppTheme.accent
                            )

                            card(
                                titulo: "Clientes",
                                valor: "\(totalClientes)",
                                icone: "person.2.fill",
                                cor: .blue
                            )
                        }


                        // MARK: - Vendas

                        card(
                            titulo: "Vendas",
                            valor: "\(totalVendas)",
                            icone: "cart.fill",
                            cor: .purple
                        )


                        // MARK: - Gráfico

                        VStack(
                            alignment: .leading,
                            spacing: 16
                        ) {

                            HStack {

                                Image(
                                    systemName: "chart.bar.fill"
                                )
                                .foregroundStyle(
                                    AppTheme.accent
                                )

                                Text("Faturamento por dia")
                                    .font(.headline)

                                Spacer()
                            }


                            if faturamentoPorDia.isEmpty {

                                Text(
                                    "Ainda não existem vendas suficientes."
                                )
                                .foregroundStyle(.secondary)

                            } else {

                                Chart(faturamentoPorDia) { item in

                                    BarMark(
                                        x: .value(
                                            "Dia",
                                            item.label
                                        ),
                                        y: .value(
                                            "Faturamento",
                                            item.total
                                        )
                                    )
                                    .foregroundStyle(
                                        AppTheme.accent.gradient
                                    )
                                    .cornerRadius(5)
                                }
                                .frame(height: 200)
                            }
                        }
                        .padding()
                        .background(AppTheme.card)
                        .clipShape(
                            RoundedRectangle(
                                cornerRadius: 18
                            )
                        )
                    }
                    .padding()
                }
            }
            .navigationTitle("Dashboard")
            .task {

                await carregarDashboard()
            }
        }
    }


    // MARK: - Card

    private func card(
        titulo: String,
        valor: String,
        icone: String,
        cor: Color
    ) -> some View {

        HStack {

            Image(systemName: icone)
                .font(.title)
                .foregroundStyle(cor)

            VStack(
                alignment: .leading,
                spacing: 4
            ) {

                Text(titulo)
                    .font(.caption)
                    .foregroundStyle(.secondary)

                Text(valor)
                    .font(.title2)
                    .bold()
            }

            Spacer()
        }
        .padding()
        .background(AppTheme.card)
        .clipShape(
            RoundedRectangle(
                cornerRadius: 18
            )
        )
    }


    // MARK: - Faturamento agrupado

    private var faturamentoPorDia: [FaturamentoDia] {

        var totais: [String: Double] = [:]

        for venda in vendas {

            let data = String(
                venda.dataVenda.prefix(10)
            )

            totais[data, default: 0] +=
                venda.valorTotal
        }

        return totais
            .sorted {
                $0.key < $1.key
            }
            .suffix(7)
            .map {

                FaturamentoDia(
                    data: $0.key,
                    total: $0.value
                )
            }
    }


    // MARK: - API

    private func carregarDashboard() async {

        do {

            async let produtosRequest =
                APIService.shared.buscarProdutos()

            async let clientesRequest =
                APIService.shared.buscarClientes()

            async let vendasRequest =
                APIService.shared.buscarVendas()


            let (
                produtosCarregados,
                clientesCarregados,
                vendasCarregadas
            ) = try await (
                produtosRequest,
                clientesRequest,
                vendasRequest
            )


            totalProdutos =
                produtosCarregados.count

            totalClientes =
                clientesCarregados.count

            totalVendas =
                vendasCarregadas.count


            vendas =
                vendasCarregadas


            faturamento =
                vendasCarregadas.reduce(0) {
                    $0 + $1.valorTotal
                }

        } catch {

            print(
                "Erro ao carregar Dashboard: \(error)"
            )
        }

        carregando = false
    }
}


// MARK: - Modelo do gráfico

private struct FaturamentoDia: Identifiable {

    let data: String
    let total: Double

    var id: String {
        data
    }


    var label: String {

        let componentes =
            data.split(separator: "-")

        guard componentes.count == 3 else {
            return data
        }

        return "\(componentes[2])/\(componentes[1])"
    }
}


#Preview {
    DashboardView()
}
