@extends('layouts.app')

@section('title', 'Guia da Lei 15.270/2025 - Simulador IRPF')

@section('header-nav')
    <x-simulador.header-nav />
@endsection

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <aside class="lg:col-span-1">
                <div class="sticky top-24 bg-white p-4 rounded-xl shadow-sm border border-neutral-200">
                    <h3 class="text-sm font-bold text-neutral-700 uppercase tracking-wider mb-4">Navegação</h3>
                    <nav class="space-y-2">
                        <a href="#visao-geral" onclick="scrollToSection('visao-geral'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Visão Geral</a>
                        <a href="#artigo-6a" onclick="scrollToSection('artigo-6a'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Art. 6-A / IR na Fonte</a>
                        <a href="#irpf-minimo" onclick="scrollToSection('irpf-minimo'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">IRPF Mínimo</a>
                        <a href="#travas" onclick="scrollToSection('travas'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Travas (Art. 16-B)</a>
                        <a href="#distribuicao-exterior" onclick="scrollToSection('distribuicao-exterior'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Distribuição no Exterior</a>
                        <a href="#estrategias" onclick="scrollToSection('estrategias'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Estratégias</a>
                        <a href="#riscos" onclick="scrollToSection('riscos'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Riscos</a>
                        <a href="{{ route('simulador.index') }}" class="nav-link block px-3 py-2 text-sm text-brand-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors font-medium">→ Calculadora</a>
                    </nav>
                </div>
            </aside>
            <div class="lg:col-span-3 space-y-8">
                <section id="visao-geral" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Visão Geral do Sistema</h2>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            A <strong>Lei nº 15.270, de 26 de novembro de 2025</strong> (conversão em lei do <strong>Projeto de Lei nº 1.087/2025</strong>, sancionado sem vetos), introduziu mudanças significativas na tributação do Imposto de Renda da Pessoa Física (IRPF) no Brasil, com vigência a partir de 1º de janeiro de 2026. <strong>Importante:</strong> Não se trata de duas normas diferentes, mas sim da mesma norma em etapas distintas do processo legislativo — o PL 1.087/2025 é o projeto que, após aprovação no Congresso, foi convertido na Lei 15.270/2025.
                        </p>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Objetivos</h3>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li>Aumentar a progressividade do sistema tributário brasileiro</li>
                            <li>Aliviar a carga tributária para rendas mais baixas</li>
                            <li>Aumentar a tributação sobre rendas mais altas e grandes fortunas</li>
                            <li>Equiparar a tributação de dividendos entre residentes e não residentes</li>
                        </ul>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Abrangência</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            A lei afeta todos os contribuintes pessoa física residentes no Brasil, com impactos diferenciados conforme a faixa de renda:
                        </p>
                        <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                            <ul class="space-y-2 text-neutral-700">
                                <li><strong>Rendimentos até R$ 5.000/mês:</strong> Isenção integral do IRPF</li>
                                <li><strong>Rendimentos entre R$ 5.000,01 e R$ 7.350/mês:</strong> Redução progressiva do imposto</li>
                                <li><strong>Dividendos acima de R$ 50.000/mês:</strong> Tributação exclusiva de 10% na fonte</li>
                                <li><strong>Rendimentos anuais acima de R$ 600.000:</strong> Sujeitos ao Imposto de Renda Mínimo</li>
                            </ul>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Principais Mudanças</h3>
                        <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                            <li><strong>Ampliação da faixa de isenção:</strong> De R$ 2.112,00 para R$ 5.000,00 mensais</li>
                            <li><strong>Nova tabela progressiva:</strong> 5 faixas com alíquotas de 0% a 27,5%</li>
                            <li><strong>Tributação de dividendos:</strong> Retenção na fonte de 10% para valores acima de R$ 50.000/mês</li>
                            <li><strong>Imposto Mínimo:</strong> Tributação adicional progressiva para altas rendas</li>
                            <li><strong>Trava de segurança:</strong> Mecanismo para evitar tributação excessiva (Art. 16-B)</li>
                        </ol>
                    </div>
                </section>

                <section id="artigo-6a" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Artigo 6-A / Imposto de Renda na Fonte</h2>
                    <div class="prose prose-sm max-w-none">
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Mecânica de Funcionamento</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            O <strong>Artigo 6-A</strong> da Lei 15.270/2025 estabelece que, a partir de 1º de janeiro de 2026, <strong>lucros e dividendos distribuídos por pessoa jurídica a pessoa física</strong> que excedam <strong>R$ 50.000,00 mensais</strong> estarão sujeitos à <strong>retenção na fonte de 10% de IRPF</strong>.
                        </p>
                        <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                            <p class="text-sm text-neutral-700 mb-2"><strong>Características importantes:</strong></p>
                            <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                <li>A retenção é <strong>definitiva</strong> e não permite deduções na base de cálculo</li>
                                <li>O limite de R$ 50.000 é aplicado <strong>por pessoa jurídica</strong> e <strong>por mês</strong></li>
                                <li>Apenas o valor que excede R$ 50.000 é tributado</li>
                                <li>Não há compensação na declaração anual de ajuste</li>
                            </ul>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Cenários Práticos</h3>
                        <div class="space-y-4 mb-4">
                            <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                <h4 class="font-semibold text-neutral-800 mb-2">Exemplo 1: Dividendos dentro do limite</h4>
                                <p class="text-sm text-neutral-700 mb-2">Um acionista recebe R$ 45.000,00 de dividendos em um mês de uma única empresa.</p>
                                <p class="text-sm text-neutral-600"><strong>Resultado:</strong> Não há retenção na fonte, pois o valor está abaixo do limite de R$ 50.000,00.</p>
                            </div>
                            <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                <h4 class="font-semibold text-neutral-800 mb-2">Exemplo 2: Dividendos acima do limite</h4>
                                <p class="text-sm text-neutral-700 mb-2">Um acionista recebe R$ 60.000,00 de dividendos em um mês de uma única empresa.</p>
                                <p class="text-sm text-neutral-700 mb-2"><strong>Cálculo:</strong></p>
                                <ul class="text-sm text-neutral-600 list-disc list-inside ml-4">
                                    <li>Valor excedente: R$ 60.000 - R$ 50.000 = R$ 10.000</li>
                                    <li>Imposto retido: R$ 10.000 × 10% = <strong>R$ 1.000,00</strong></li>
                                </ul>
                            </div>
                            <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                <h4 class="font-semibold text-neutral-800 mb-2">Exemplo 3: Múltiplas empresas</h4>
                                <p class="text-sm text-neutral-700 mb-2">Um investidor recebe R$ 40.000 de cada uma de 3 empresas diferentes no mesmo mês (total: R$ 120.000).</p>
                                <p class="text-sm text-neutral-600"><strong>Resultado:</strong> Não há retenção, pois cada pagamento individual está abaixo de R$ 50.000,00. O limite é aplicado por pessoa jurídica, não pelo total recebido.</p>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Regime de Transição (Inciso 3º)</h3>
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-4">
                            <p class="text-sm text-neutral-700 mb-2"><strong>Isenção temporária:</strong></p>
                            <p class="text-sm text-neutral-700">
                                Lucros e dividendos referentes a <strong>resultados apurados até 31 de dezembro de 2025</strong>, cuja distribuição tenha sido <strong>deliberada e aprovada até essa data</strong>, permanecem isentos de tributação, mesmo que o pagamento ocorra após 1º de janeiro de 2026, desde que realizado <strong>até 31 de dezembro de 2028</strong>.
                            </p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <p class="text-sm text-neutral-700"><strong>⚠️ Atenção:</strong> Para aproveitar o regime de transição, é necessário que tanto a apuração do resultado quanto a deliberação da distribuição tenham ocorrido até 31/12/2025.</p>
                        </div>
                    </div>
                </section>

                <section id="irpf-minimo" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Imposto de Renda Mínimo (IRPF Min.)</h2>
                    <div class="prose prose-sm max-w-none">
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Funcionamento</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            O <strong>Imposto de Renda Mínimo</strong> é uma tributação adicional progressiva aplicada a contribuintes pessoa física com <strong>rendimentos anuais superiores a R$ 600.000,00</strong>. O objetivo é garantir que contribuintes de alta renda tenham uma alíquota efetiva mínima, mesmo considerando rendimentos isentos ou tributados exclusivamente na fonte.
                        </p>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Base de Cálculo</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            A base de cálculo do IRPF Min. inclui:
                        </p>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li>Todos os rendimentos tributáveis</li>
                            <li>Rendimentos isentos (exceto os especificados em lei)</li>
                            <li>Rendimentos tributados exclusivamente na fonte</li>
                            <li>Lucros e dividendos recebidos</li>
                        </ul>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Tabela de Alíquotas Progressivas</h3>
                        <div class="overflow-x-auto mb-4">
                            <table class="min-w-full border border-neutral-300 rounded-lg">
                                <thead class="bg-brand-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-neutral-800 border-b border-neutral-300">Faixa de Renda Anual</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-neutral-800 border-b border-neutral-300">Alíquota</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm text-neutral-700">
                                    <tr class="border-b border-neutral-200">
                                        <td class="px-4 py-3">Até R$ 600.000,00</td>
                                        <td class="px-4 py-3 text-center">0%</td>
                                    </tr>
                                    <tr class="border-b border-neutral-200 bg-neutral-50">
                                        <td class="px-4 py-3">De R$ 600.000,01 a R$ 800.000,00</td>
                                        <td class="px-4 py-3 text-center font-semibold">5%</td>
                                    </tr>
                                    <tr class="border-b border-neutral-200">
                                        <td class="px-4 py-3">De R$ 800.000,01 a R$ 1.000.000,00</td>
                                        <td class="px-4 py-3 text-center font-semibold">7,5%</td>
                                    </tr>
                                    <tr class="border-b border-neutral-200 bg-neutral-50">
                                        <td class="px-4 py-3">De R$ 1.000.000,01 a R$ 1.200.000,00</td>
                                        <td class="px-4 py-3 text-center font-semibold">9%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3">Acima de R$ 1.200.000,00</td>
                                        <td class="px-4 py-3 text-center font-semibold text-brand-700">10%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Exemplo de Cálculo</h3>
                        <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200 mb-4">
                            <p class="text-sm text-neutral-700 mb-3"><strong>Cenário:</strong> Contribuinte com rendimentos anuais de R$ 900.000,00</p>
                            <div class="space-y-2 text-sm text-neutral-700">
                                <p><strong>Cálculo progressivo:</strong></p>
                                <ul class="list-disc list-inside ml-4 space-y-1">
                                    <li>1ª faixa (R$ 600.000,01 a R$ 800.000): R$ 200.000 × 5% = <strong>R$ 10.000,00</strong></li>
                                    <li>2ª faixa (R$ 800.000,01 a R$ 900.000): R$ 100.000 × 7,5% = <strong>R$ 7.500,00</strong></li>
                                </ul>
                                <p class="mt-2 font-semibold">IRPF Min. Total: <strong>R$ 17.500,00</strong></p>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Deduções Permitidas</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Podem ser deduzidos da base de cálculo do IRPF Min.:
                        </p>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2">
                            <li>O imposto devido na Declaração de Ajuste Anual (IRPF normal)</li>
                            <li>O imposto retido na fonte sobre rendimentos incluídos na base de cálculo</li>
                            <li>O imposto pago sobre rendas auferidas no exterior (respeitando acordos internacionais)</li>
                        </ul>
                    </div>
                </section>

                <section id="travas" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Travas - Artigo 16-B</h2>
                    <div class="prose prose-sm max-w-none">
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Trava de Segurança para Tributação Excessiva</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            O <strong>Artigo 16-B</strong> da Lei 15.270/2025 estabelece um mecanismo de segurança para evitar que a carga tributária total sobre lucros e dividendos ultrapasse limites considerados excessivos, garantindo justiça fiscal e evitando bitributação.
                        </p>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Mecanismo de Funcionamento</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            A trava é acionada quando a <strong>soma das alíquotas efetivas</strong> de tributação ultrapassa os limites estabelecidos:
                        </p>
                        <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                            <p class="text-sm text-neutral-700 mb-2"><strong>Limites de alíquota efetiva total:</strong></p>
                            <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                <li><strong>34%</strong> para empresas em geral</li>
                                <li><strong>40%</strong> para instituições financeiras</li>
                                <li><strong>45%</strong> para atividades específicas regulamentadas</li>
                            </ul>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Cálculo da Trava</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            A trava considera:
                        </p>
                        <ol class="list-decimal list-inside text-neutral-700 space-y-2 mb-4">
                            <li><strong>Alíquota efetiva da pessoa jurídica:</strong> Soma do IRPJ e CSLL sobre o lucro</li>
                            <li><strong>Alíquota efetiva do IRPF:</strong> Imposto devido pelo beneficiário pessoa física</li>
                            <li><strong>Verificação:</strong> Se a soma ultrapassar o limite, aplica-se um redutor no IRPF</li>
                        </ol>
                        <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200 mb-4">
                            <h4 class="font-semibold text-neutral-800 mb-2">Exemplo Prático</h4>
                            <p class="text-sm text-neutral-700 mb-2">Empresa com alíquota efetiva de 30% (IRPJ + CSLL) distribui dividendos a pessoa física.</p>
                            <p class="text-sm text-neutral-700 mb-2">Sem a trava, o IRPF sobre dividendos seria de 10%, totalizando 40%.</p>
                            <p class="text-sm text-neutral-600"><strong>Com a trava:</strong> Se o limite for 34%, o IRPF é reduzido para 4%, mantendo o total em 34%.</p>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Benefícios</h3>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2">
                            <li>Evita tributação excessiva sobre a mesma base de cálculo</li>
                            <li>Garante previsibilidade e justiça fiscal</li>
                            <li>Protege pequenos e médios investidores</li>
                            <li>Mantém a competitividade do sistema tributário brasileiro</li>
                        </ul>
                    </div>
                </section>

                <section id="distribuicao-exterior" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Implicações com a Distribuição no Exterior</h2>
                    <div class="prose prose-sm max-w-none">
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Tributação de Não Residentes</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            A Lei 15.270/2025 estabelece que <strong>lucros e dividendos remetidos ao exterior</strong>, tanto para pessoas físicas quanto jurídicas não residentes, estarão sujeitos à <strong>retenção na fonte de 10% de IRPF</strong>, <strong>independentemente do valor</strong>.
                        </p>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200 mb-4">
                            <p class="text-sm text-neutral-700"><strong>⚠️ Diferença importante:</strong> Para não residentes, não há limite de R$ 50.000/mês. Qualquer valor remetido ao exterior está sujeito à retenção de 10%.</p>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Impactos em Investimentos Estrangeiros</h3>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li><strong>Redução da rentabilidade líquida:</strong> Investidores estrangeiros terão retorno líquido menor</li>
                            <li><strong>Possível redução de investimentos:</strong> A nova tributação pode desestimular investimentos estrangeiros no Brasil</li>
                            <li><strong>Impacto em fundos internacionais:</strong> Fundos de investimento estrangeiros podem revisar suas estratégias</li>
                        </ul>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Acordos Internacionais</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            O Brasil possui <strong>acordos para evitar bitributação</strong> com diversos países. Nestes casos:
                        </p>
                        <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                            <ul class="list-disc list-inside text-sm text-neutral-700 space-y-2">
                                <li>A tributação pode ser reduzida conforme o acordo específico</li>
                                <li>É possível creditar o imposto pago no Brasil contra o imposto devido no país de residência</li>
                                <li>Recomenda-se consultar o acordo específico entre Brasil e o país do beneficiário</li>
                            </ul>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Planejamento Tributário</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Para empresas com investidores estrangeiros, é importante:
                        </p>
                        <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                            <li>Verificar a existência de acordos de bitributação</li>
                            <li>Calcular o impacto líquido da nova tributação</li>
                            <li>Considerar estruturas alternativas de investimento</li>
                            <li>Comunicar claramente aos investidores sobre as mudanças</li>
                        </ol>
                    </div>
                </section>

                <section id="estrategias" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Estratégias</h2>
                    <div class="prose prose-sm max-w-none">
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Planejamento Tributário</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Diante das mudanças introduzidas pela Lei 15.270/2025, é fundamental revisar estratégias de planejamento tributário para otimizar a carga fiscal.
                        </p>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">1. Planejamento de Distribuição de Dividendos</h3>
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-4">
                            <p class="text-sm text-neutral-700 mb-2"><strong>Estratégia:</strong> Antecipar distribuições</p>
                            <p class="text-sm text-neutral-700">
                                Empresas podem considerar <strong>antecipar a distribuição de dividendos</strong> referentes a lucros apurados até 31 de dezembro de 2025, aproveitando a isenção prevista no regime de transição. Distribuições aprovadas até essa data e pagas até 2028 permanecem isentas.
                            </p>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">2. Reestruturação de Remuneração</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Para sócios e administradores, pode ser vantajoso revisar a composição entre:
                        </p>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li><strong>Pró-labore:</strong> Sujeito à tabela progressiva (0% a 27,5%)</li>
                            <li><strong>Dividendos:</strong> Isentos até R$ 50.000/mês, depois 10% na fonte</li>
                        </ul>
                        <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200 mb-4">
                            <p class="text-sm text-neutral-700"><strong>⚠️ Atenção:</strong> A escolha entre pró-labore e dividendos deve considerar não apenas a carga tributária, mas também questões trabalhistas, previdenciárias e de governança.</p>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">3. Otimização de Deduções</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Maximizar o uso de deduções legais pode reduzir significativamente a base de cálculo:
                        </p>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li><strong>Dependentes:</strong> R$ 2.275,08 por dependente</li>
                            <li><strong>Saúde:</strong> Sem limite de dedução</li>
                            <li><strong>Educação:</strong> Até R$ 3.561,50 por dependente</li>
                            <li><strong>PGBL:</strong> Até 12% da renda bruta tributável</li>
                        </ul>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">4. Estratégias para Altas Rendas</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Para contribuintes sujeitos ao IRPF Min. (renda acima de R$ 600.000/ano):
                        </p>
                        <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                            <li>Monitorar a alíquota efetiva total para garantir que não ultrapasse os limites</li>
                            <li>Considerar o uso da trava de segurança (Art. 16-B) quando aplicável</li>
                            <li>Avaliar a distribuição de rendimentos ao longo do ano para otimizar a carga tributária</li>
                            <li>Consultar especialistas em planejamento tributário para estruturas mais complexas</li>
                        </ol>
                    </div>
                </section>

                <section id="riscos" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Riscos</h2>
                    <div class="prose prose-sm max-w-none">
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Complexidade na Apuração</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            As novas regras aumentam significativamente a complexidade na apuração do imposto devido, exigindo:
                        </p>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li>Controle mensal detalhado de dividendos recebidos por fonte pagadora</li>
                            <li>Cálculo preciso do IRPF Min. considerando todas as faixas progressivas</li>
                            <li>Verificação da aplicação das travas de segurança</li>
                            <li>Monitoramento do regime de transição para dividendos</li>
                        </ul>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-4">
                            <p class="text-sm text-neutral-700"><strong>⚠️ Risco:</strong> Erros na apuração podem resultar em multas, juros e possíveis autuações fiscais.</p>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Possíveis Controvérsias Jurídicas</h3>
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Mudanças na legislação podem gerar interpretações divergentes e litígios, especialmente em relação a:
                        </p>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li><strong>Aplicação do IRPF Min.:</strong> Definição precisa da base de cálculo e rendimentos incluídos</li>
                            <li><strong>Regime de transição:</strong> Interpretação sobre o que constitui "deliberação" e "aprovação" até 31/12/2025</li>
                            <li><strong>Trava de segurança:</strong> Cálculo da alíquota efetiva e aplicação dos redutores</li>
                            <li><strong>Distribuição no exterior:</strong> Interação com acordos internacionais de bitributação</li>
                        </ul>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Penalidades e Implicações Legais</h3>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200 mb-4">
                            <p class="text-sm text-neutral-700 mb-2"><strong>Multas e juros:</strong></p>
                            <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                <li>Multa por atraso no recolhimento: até 20% do valor devido</li>
                                <li>Juros de mora: Selic acumulada</li>
                                <li>Multa por omissão ou erro: 75% a 150% do imposto devido</li>
                            </ul>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Riscos Operacionais</h3>
                        <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                            <li><strong>Falta de sistemas adequados:</strong> Empresas podem não ter sistemas atualizados para calcular corretamente as novas obrigações</li>
                            <li><strong>Treinamento insuficiente:</strong> Equipes contábeis e fiscais podem não estar preparadas para as mudanças</li>
                            <li><strong>Prazos apertados:</strong> A implementação a partir de 2026 pode não dar tempo suficiente para adaptação</li>
                        </ul>
                        <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Recomendações para Mitigação</h3>
                        <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                            <li>Investir em sistemas de controle e apuração adequados</li>
                            <li>Treinar equipes sobre as novas regras</li>
                            <li>Consultar especialistas em direito tributário</li>
                            <li>Manter documentação detalhada de todas as operações</li>
                            <li>Realizar revisões periódicas dos cálculos</li>
                            <li>Considerar seguro de responsabilidade fiscal</li>
                        </ol>
                    </div>
                </section>

                <section id="calculadora-link" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <h2 class="text-2xl font-bold text-neutral-800 mb-4">Calculadora</h2>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-neutral-700 leading-relaxed mb-4">
                            Utilize nossa <strong>calculadora interativa</strong> para simular o impacto das mudanças introduzidas pela Lei 15.270/2025 no seu caso específico.
                        </p>
                        <div class="bg-brand-50 p-6 rounded-lg border border-brand-200 text-center">
                            <p class="text-neutral-700 mb-4">A calculadora permite:</p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-6 text-left max-w-md mx-auto">
                                <li>Calcular o imposto devido considerando as novas faixas</li>
                                <li>Simular a tributação de dividendos acima de R$ 50.000/mês</li>
                                <li>Comparar o regime simplificado com deduções legais</li>
                                <li>Estimar o impacto do IRPF Mínimo</li>
                            </ul>
                            <a href="{{ route('simulador.index') }}" class="inline-block px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                                Acessar Calculadora →
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="/js/simulador.js"></script>
    @endpush
@endsection
