<?php
	require '../conexao.php';
	session_start();

	function getFormsJustificativa($conn, $id_usuario){
		$formsJustificativa = $conn -> query("SELECT
			'Justificativa' AS tipo_formulario,
			tb_formsJustificativa.id_formJustificativa,
			tb_formsJustificativa.data_envio,
			tb_formsJustificativa.status,
			tb_formsJustificativa.observacoes_coordenador,
			tb_usuarios.id_usuario,
			tb_cursos.sigla
		FROM tb_formsJustificativa
		INNER JOIN tb_usuarios 
			ON tb_usuarios.id_usuario = tb_formsJustificativa.id_usuario
		INNER JOIN tb_cursos
			ON tb_formsJustificativa.id_curso = tb_cursos.id_curso
		WHERE tb_formsJustificativa.id_usuario = $id_usuario
		") -> fetchAll(PDO::FETCH_ASSOC);

		return $formsJustificativa;
	}
	function getFormsReposicao($conn, $id_usuario){
		$formsReposicao = $conn -> query("SELECT 
		'Reposição' AS tipo_formulario,
			tb_formsReposicao.id_formReposicao,
			tb_formsReposicao.data_envio, 
			tb_formsReposicao.status,
			tb_formsReposicao.observacoes_coordenador, 
			tb_cursos.sigla,
			tb_usuarios.id_usuario
		FROM tb_formsReposicao
		INNER JOIN tb_usuarios 
			ON tb_usuarios.id_usuario = tb_formsReposicao.id_usuario
		INNER JOIN tb_cursos 
			ON tb_formsReposicao.id_curso = tb_cursos.id_curso
		WHERE tb_formsReposicao.id_usuario = $id_usuario
		") -> fetchAll(PDO::FETCH_ASSOC);

		return $formsReposicao;
	}

	function mapearFormularios($form, $tipoID){
		return [
			'tipo' => $form['tipo_formulario'],
			$tipoID => $form[$tipoID],
			'data_envio' => $form['data_envio'],
			'status' => $form['status'],
			'observacoes' => $form['observacoes_coordenador'],
			'sigla' => $form['sigla'],
			'id_usuario' => $form['id_usuario']
		];
	}
	function criarLista($formsReposicao, $formsJustificativa,){
		$formularios = [];
		foreach($formsReposicao as $form){
			$formularios[] = mapearFormularios($form, 'id_formReposicao'); 
		}
		foreach($formsJustificativa as $form){
			$formularios[] = mapearFormularios($form, 'id_formJustificativa');
		}

		return $formularios;
	}

	function exibirFormulario($formulario){
		if ($formulario['tipo'] == 'Justificativa'){
			return "../exibirForm/exibirJustificativa.php?id_formJustificativa=$formulario[id_formJustificativa]";    
		} else {
			return "../exibirForm/exibirReposicao.php?id_formReposicao=$formulario[id_formReposicao]";
		}
	}

	function exibirStatus($formulario){
		if ($formulario['status'] == 'APROVADO'){
			return "<td style='color: #fff; background-color: rgb(3, 139, 3);'><b><i class='fa-solid fa-circle-check'> </i>  $formulario[status]</b></td>";
		} else if ($formulario['status'] == 'REPROVADO'){
			return "<td style='color: #fff; background-color: rgb(177, 7, 7);'><b><i class='fa-solid fa-circle-xmark'> </i>  $formulario[status]</b></td>";
		} else {
			return "<td style='color: #fff; background-color: rgb(209, 198, 47);'><b><i class='fa-solid fa-circle-exclamation'> </i>  $formulario[status]</b></td>";
		}
	}

	$formsReposicao = getFormsReposicao($conn, $_SESSION['id_usuario']);
	$formsJustificativa = getFormsJustificativa($conn, $_SESSION['id_usuario']);

	$formularios = criarLista($formsReposicao, $formsJustificativa);

	// Ordenando pela data de envio
	usort($formularios, function($a, $b) {
		return strtotime($a['data_envio']) - strtotime($b['data_envio']);
	});
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Status - Área do Professor</title>
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
	<link rel="stylesheet" type="text/css" href="../Style/main.css" />
	<script src="../Components/footer.js" type="text/javascript" defer></script>
	<script src="../Components/footer.js" type="text/javascript" defer></script>
	<script src="../Components/modal.js" type="text/javascript" defer></script>
	<script src="https://kit.fontawesome.com/26c14cdb57.js" crossorigin="anonymous"></script>

<style>
	thead {
	color: #fff;
	background-color: #a90e0b;
	}

	textarea {
	resize: none;
	}
</style>
	
</head>

<body>
<header>
    <nav>
        <ul>
            <li><a href="../index.php">Início</a></li>
            <li><a href="justificativa.php">Justificativa de Faltas</a></li>
            <li><a href="reposicao.php">Plano de Reposição</a></li>
            <li><a href="status.php">Status</a></li>
            <li style="float: right;"><a href="../auth/logout.php">Sair</a></li>
            <li style="float: right;"><a style="text-decoration-line: underline;" href="status.php">Área do Professor</a></li>
            <li style="float: right;" ><a href="../coordenador/PagCoord.php">Área do Coordenador</a></li>
        </ul>
    </nav>
</header>
	<main>
	<h1><i class="fa-solid fa-user-gear"></i>&nbsp;Área do Professor</h1>
	<h2>Lista de requisições</h2>

	<table style="text-align:center; width:100%;">
		<thead>
		<tr>
			<td style="width: 12%;">Nº da requisição</td>
			<td>Assunto</td>
			<td>Curso</td>
			<td>Data de envio</td>
			<td>Visualizar formulário</td>
			<td>Status</td>
			<td>Observação</td>
		</tr>
		</thead>
		<tbody>
			<?php 
				$cont = 0;
				foreach ($formularios as $formulario):
					$cont++
			?>
			<tr>
			<td><?= $cont ?></td>
			<td><?= $formulario['tipo'] ?></td>
			<td><?= $formulario['sigla']?></td>
			<td><?= date('d/m/Y',strtotime($formulario['data_envio']))?></td>
			<td><a href="<?= exibirFormulario($formulario) ?>" target="_blank"><button class="botao" title="Visualizar formulário"><i class="fa-solid fa-file-contract"></i></button></a></td>
			<?= exibirStatus($formulario) ?>
			<td><button class="botao" title="Visualizar observação" onclick="openModal('observacao')"><i class="fa-solid fa-comment"></i></button></td>
			
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
	<div id="modalObservacao" class="modal">
  <div class="modal-content">
	<span class="close" onclick="closeModal()">&times;</span>
	<p>Observações do Coordenador</p>
	<textarea name="observacao" id="observacao" cols="40" rows="10" readonly></textarea>
	<div class="button-container">
	  <button onclick="closeModal()">Fechar</button>
	</div>
  </div>
</div>
	</div>
	</main>

	<footer-component></footer-component>
</body>
	</html>

