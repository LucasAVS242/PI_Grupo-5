<?php
require '../conexao.php';
session_start();
if(!isset($_SESSION['id_usuario'])){
    header('Location: ../index.html');
}

function getDisciplina($conn, $id_usuario){
    $disciplinas = $conn -> query("SELECT tb_disciplinas.nome_disciplina, tb_disciplinas.qtde_aulas, tb_disciplinas.id_disciplina, tb_usuarios.id_usuario
    FROM tb_disciplinas
    INNER JOIN tb_usuarioDisciplina 
        ON tb_disciplinas.id_disciplina = tb_usuarioDisciplina.id_disciplina
    INNER JOIN tb_usuarios 
        ON tb_usuarios.id_usuario = tb_usuarioDisciplina.id_usuario
    WHERE tb_usuarios.id_usuario = $id_usuario
    ") -> fetchAll(PDO::FETCH_ASSOC);
    return $disciplinas;
}

function exibirDisciplinas($disciplinas){
    foreach ($disciplinas as $disciplina){
        echo "<option value='". $disciplina['id_disciplina']. "'>" . $disciplina['nome_disciplina'] . '</option>';                                    
    }
}

function fazerUpload($arquivo){
    if ($arquivo['error'] === UPLOAD_ERR_OK){
        $nomeArquivo = uniqid() . "-" . $arquivo['name'];
        if (move_uploaded_file($arquivo['tmp_name'], "../uploads/$nomeArquivo")){
            return $nomeArquivo;
        }
    }
}

function setFormJustificativa($conn, $curso, $data_envio, $motivo, $nomeArquivo){
    $stmt = $conn -> prepare("INSERT INTO tb_formsJustificativa (id_usuario, id_curso, data_envio, motivo, nome_arquivo, status, observacoes_coordenador) VALUES (?, ?, ?, ?, ?, 'PENDENTE', ' ')");
    $stmt -> execute([$_SESSION['id_usuario'], $curso, $data_envio, $motivo, $nomeArquivo]);
}

function setAulasNaoMinistradas($data,$conn,$idFormulario,$qtde,$disciplina){
    for($i = 0; $i < count($data); $i++){
        if(!empty($data[$i]) && $data[$i] != '0000-00-00' && !empty($qtde[$i])){
            $stmt = $conn -> prepare("INSERT INTO tb_aulasNaoMinistradas(data, quantidade_aulas, id_disciplina, id_formJustificativa) VALUES (?, ?, ?, ?)");
            $stmt -> execute([$data[$i], $qtde[$i], $disciplina[$i], $idFormulario]);
        }
    }
}

$disciplinas = getDisciplina($conn, $_SESSION['id_usuario']);

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if(isset($_POST['selectFaltaMedica'])){
       $motivo = $_POST['selectFaltaMedica'];
    } else {
        $motivo = $_POST['selectFaltaLT'];
    }
    $arquivo = $_FILES['comprovante'];
    $data_envio = date('Y-m-d');
    $curso = $_POST['curso'];
    $nomeArquivo = fazerUpload($arquivo);
    setFormJustificativa($conn, $curso, $data_envio, $motivo,  $nomeArquivo);

    $idFormulario = $conn -> lastInsertId();
    $data = $_POST['data'];
    $disciplina = $_POST['disciplina'];
    $qtde = $_POST['qtde'];
    setAulasNaoMinistradas($data, $conn, $idFormulario,$qtde,$disciplina);
        
    header('Location: reposicao.php?id_formJustificativa=' . $idFormulario);
    exit;
    }
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de justificativa de faltas - Área do Professor</title>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="../Components/footer.js" type="text/javascript" defer></script>
    <script src="../Components/validacao.js" type="text/javascript" defer></script>
    <link rel="stylesheet" type="text/css" href="../Style/main.css">

    <style>
        input {
            border-bottom: solid thin;
        }

        form {
            border: black 2px solid;
            border-radius: 10px;
            padding: 2%;
        }

        section {
            border: black 2px solid;
            padding: 1%;
            margin: 2%;
        }

        .center-block {
            display: block;
            margin-right: auto;
            margin-left: auto;
        }
    </style>

    <script>
        function validarSelecao(event) {
            const select1 = document.getElementById("selectFaltaMedica");
            const select2 = document.getElementById("selectFaltaLT");

            // Verifica se pelo menos um motivo tenha sido selecionado
            if ((!select1.value) && (!select2.value)) {
                alert("Selecione o motivo da falta.");
                return false;
            }

            // Verifica se apenas um motivo tenha selecionado
            if ((select1.value && !select2.value) || (!select1.value && select2.value)) {
                return true;
            } else {
                alert("Por favor, selecione apenas um motivo.");
                select1.value = "";
                select2.value = "";
                event.preventDefault();
                return false;
            }
        }
    </script>
</head>

<body>
    <nav>
        <ul>
            <li><a href="../index.php">Início</a></li>
            <li><a href="justificativa.php">Justificativa de Faltas</a></li>
            <li><a href="status.php">Status</a></li>
            <li style="float: right;"><a href="../auth/logout.php">Sair</a></li>
            <li style="float: right;"><a style="text-decoration-line: underline;" href="status.php">Área do
                    Professor</a></li>
            <li style="float: right;"><a href="../coordenador/PagCoord.php">Área do Coordenador</a></li>
        </ul>
    </nav>

    <main id="justificativa">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <h1 style="text-align: center;">Justificativa de Faltas</h1>
                    <form id="form" method="POST" enctype="multipart/form-data" onsubmit="validarSelecao(event)">
                        <div class="mb-3">

                            <label><strong>Nome:</strong> </label>
                            <?= $_SESSION['nome'] ?>
                            <label><strong>Mátricula:</strong> </label>
                            <?= $_SESSION['matricula'] ?>
                            <br>
                            <p><strong>FUNÇÃO:</strong> Professor de Ensino Superior <strong>REGIME JURÍDICO:</strong> CLT</p>
                            <strong><label>CURSO(S) ENVOLVIDO(S) NA AUSÊNCIA: </label></strong>
                                <input class="form-check-input" type="checkbox" name="curso" id="cst-dsm" value="1" onclick="onlyOne(this)"> CST-DSM
                                <input class="form-check-input" type="checkbox" name="curso" id="cst-ge" value="2" onclick="onlyOne(this)"> CST-GE
                                <input class="form-check-input" type="checkbox" name="curso" id="cst-gpi" value="3" onclick="onlyOne(this)"> CST-GPI
                                <input class="form-check-input" type="checkbox" name="curso" id="cst-gti" value="4" onclick="onlyOne(this)"> CST-GTI
                                <input class="form-check-input" type="checkbox" name="curso" id="hae" value="HAE"> HAE </strong>
                            <span id="mensagemErro" style="color: red; padding-left: 1%;"></span>

                            <br><br>

                            <h5>Dados da(s) aulas não ministradas</h5>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ordem</th>
                                        <th>Data</th>
                                        <th>Nº de Aulas</th>
                                        <th>Disciplinas</th>
                                    </tr>
                                </thead>
                                <tbody id="conteinerLinhas">
                                    <tr>
                                        <td>#</td>
                                        <td><input type="date" class="form-control" name="data[]" required></td>
                                        <td><input type="number" class="form-control" min="1" name='qtde[]' required></td>
                                        <td>
                                            <select name='disciplina[]' class="form-control">
                                                <?= exibirDisciplinas($disciplinas) ?>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="botao" onclick="adicionarLinha()">Adicionar disciplina</button>
                            <button type="button" class="botao" onclick="removerUltimaLinha()">Remover Disciplina</button>
                            <br><br>

                            <p>*Selecionar um item e anexar o comprovante.</p>

                            <h3 class="text-decoration-underline">Motivo da falta</h3>

                            <select style="margin-bottom: 5%;" class="form-select" name="motivo" id="motivo" required>
                                <option value="" selected>Selecione um motivo</option>
                                <option value="faltaMedica">Licença e falta médica</option>
                                <option value="faltaLT">Falta prevista na legisação trabalhista</option>
                            </select>

                            

                            <div id="faltaMedica" class="motivoDiv" hidden>
                                <h4>Licença e falta médica</h4>
                                <select class="form-select" name="selectFaltaMedica" id="selectFaltaMedica">
                                    <option value="" disabled selected>Selecione uma opção</option>
                                    <option value="Falta Médica">Falta Médica (Atestado médico de 1 dia)</option>
                                    <option value="Comparecimento ao Médico">Comparecimento ao Médico</option>
                                    <option value="Licença-Saúde">Licença-Saúde (Atestado médico igual ou superior a 2 dias)</option>
                                    <option value="Licença-Maternidade">Licença-Maternidade (Atestado médico até 15 dias)</option>
                                </select>
                            </div>

                            <div id="faltaLT" class="motivoDiv" hidden>
                                <h4>Falta prevista na legislação trabalhista</h4>
                                <select class="form-select" name="selectFaltaLT" id="selectFaltaLT">
                                    <option value="" disabled selected>Selecione uma opção</option>
                                    <option value="Falecimento de cônjuge, pai, mãe, filho">Falecimento de cônjuge, pai, mãe, filho. (9 dias
                                        consecutivos)</option>
                                    <option value="Falecimento ascendente">Falecimento ascendente (exceto pai e mãe), descendente
                                        (exceto filho), irmão ou pessoa declarada na CTPS, que viva sob sua dependência econômica. (2
                                        dias consecutivos)</option>
                                    <option value="Casamento">Casamento. (9 dias consecutivos)</option>
                                    <option value="Nascimento de filho">Nascimento de filho, no decorrer da primeira semana. (5
                                        dias)</option>
                                    <option value="Acompanhar esposa ou companheira no período de gravidez">Acompanhar esposa ou companheira no período de gravidez,
                                        em consultas médicas e exames complementares. (Até 2 dias)</option>
                                    <option value="Acompanhar filho de até 6 anos em consulta médica">Acompanhar filho de até 6 anos em consulta médica. (1 dia
                                        por ano)</option>
                                    <option value="Doação voluntária de sangue">Doação voluntária de sangue. (1 dia em cada 12 meses de
                                        trabalho)</option>
                                    <option value="Alistamento como eleitor">Alistamento como eleitor. (Até 2 dias consecutivos ou
                                        não)</option>
                                    <option value="Convocação para depoimento judicial">Convocação para depoimento judicial</option>
                                    <option value="Comparecimento como jurado no Tribunal do Júri">Comparecimento como jurado no Tribunal do Júri</option>
                                    <option value="Convocação para serviço eleitoral">Convocação para serviço eleitoral</option>
                                    <option value="Dispensa dos dias devido à nomeação para compor as mesas receptoras ou juntas eleitorais nas eleições ou requisitado para auxiliar seus trabalhos">Dispensa dos dias devido à nomeação para compor as mesas
                                        receptoras ou juntas eleitorais nas eleições ou requisitado para auxiliar seus trabalhos (Lei nº
                                        9.504/97)</option>
                                    <option value="Realização de Prova de Vestibular para ingresso em estabelecimento de ensino superior">Realização de Prova de Vestibular para ingresso em
                                        estabelecimento de ensino superior</option>
                                    <option value="Comparecimento necessário como parte na Justiça do Trabalho">Comparecimento necessário como parte na Justiça do
                                        Trabalho (Enunciado TST nº 155). (Horas necessárias)</option>
                                    <option value="Atrasos decorrentes de acidente">Atrasos decorrentes de acidente</option>
                                </select>
                            </div>


                            <br>


                            <section>
                                <p>Campo para envio de comprovante</p>
                                <input class="botao" type="file" name="comprovante" id="arquivo" accept=".pdf" required>
                            </section>

                            <br>


                            <button id="gerarPDF" class="botao" type="button" onclick="printForm()">Gerar PDF do documento</button>
                            <input class="botao" type="submit" value="Avançar">

                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <footer-component></footer-component>

    <script>
        
        // Mostra as opções baseado no motivo da falta
        document.getElementById('motivo').addEventListener('change', function() {
            document.querySelectorAll('.motivoDiv').forEach(div => div.hidden = true);
            let divToShow = document.getElementById(this.value);
            if (divToShow) divToShow.hidden = false;
        });

        // Código para gerar PDF
        function printForm() {
            const form = document.getElementById('form');
            const body = document.body;
            const originalContent = body.innerHTML;

            body.innerHTML = '';
            body.appendChild(form);

            window.print();

            body.innerHTML = originalContent;
        }
        
        function onlyOne(checkbox) {
            var checkboxes = document.getElementsByName(checkbox.name)
            checkboxes.forEach((item) => {
                if (item !== checkbox) item.checked = false
            })
        }
       
        document.addEventListener("DOMContentLoaded", () => {
            // Define a numeração inicial ao carregar a página
            atualizarNumeracao();
        })

        function adicionarLinha() {
            // Cria uma nova linha <tr> com o conteúdo desejado
            const novaLinha = document.createElement("tr");
            novaLinha.innerHTML = `
                <td></td>
                <td><input type="date" class="form-control" name="data[]" required></td>
                <td><input type="number" class="form-control" min="1" name="qtde[]" required></td>
                <td>
                    <select name="disciplina[]" class="form-control">
                        <?= exibirDisciplinas($disciplinas) ?>
                    </select>
                </td>
            `;

            // Adiciona a nova linha ao container
            const conteinerLinhas = document.getElementById("conteinerLinhas");
            conteinerLinhas.appendChild(novaLinha);

            // Atualiza os números das linhas
            atualizarNumeracao();
        }

        // Função para remover a última linha
        function removerUltimaLinha() {
            const conteinerLinhas = document.getElementById("conteinerLinhas");
            const linhas = conteinerLinhas.querySelectorAll("tr");

            // Verifica se há mais de uma linha antes de tentar remover
            if (linhas.length > 1) { // Mantém a linha inicial
                conteinerLinhas.removeChild(linhas[linhas.length - 1]); // Remove a última linha
                atualizarNumeracao(); // Atualiza a numeração após a remoção
            } else {
                alert("Pelo menos uma disciplina tem que ser preenchida!"); // Mensagem caso não haja mais linhas
            }
        }

        // Função para atualizar a numeração da primeira coluna
        function atualizarNumeracao() {
            const linhas = document.querySelectorAll("#conteinerLinhas tr");
            linhas.forEach((linha, index) => {
                linha.cells[0].innerText = (index + 1).toString().padStart(2, '0');
            });
        }
    </script>
</body>

</html>