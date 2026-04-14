<?php
// navbar.php - Componente de Menu Suspenso
$perfil_atual = $_SESSION['usuario']['perfil'] ?? 'aluno';
// Garantindo UTF-8 na exibição do nome vindo do Supabase
$nome_exibicao = ($_SESSION['usuario']['nome_turma'] ?? '') . ' | ' . mb_convert_case($_SESSION['usuario']['nome_guerra'] ?? '', MB_CASE_TITLE, "UTF-8");
?>

<style>
    @media (min-width: 768px) {
        .dropdown-menu .dropdown-toggle:after {
            border-top: .3em solid transparent; border-right: 0;
            border-bottom: .3em solid transparent; border-left: .3em solid;
            float: right; margin-top: .5em;
        }
        .dropdown-submenu { position: relative; }
        .dropdown-submenu > .dropdown-menu {
            top: 0; left: 100%; margin-top: -6px; margin-left: -1px;
            border-radius: 0 6px 6px 6px; display: none;
        }
        .dropdown-submenu:hover > .dropdown-menu { display: block; }
    }
</style>

<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm mb-3 p-2 rounded no-print">
    <div class="container-fluid">
        <a class="navbar-brand text-secondary fw-bold" href="index.php">
            <small><i class="bi bi-shield-check text-primary me-2"></i>SGP | <?= ($perfil_atual === 'aluno' ? 'Aluno(a) ' : '') . $nome_exibicao ?></small>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($perfil_atual === 'admin' || $perfil_atual === 'usuario'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>GESTÃO
                        </a>
                        <ul class="dropdown-menu shadow-sm">
                            <li><a class="dropdown-item" href="alunos.php"><i class="bi bi-people-fill me-2"></i>Gestão de Alunos</a></li>
                            <li><a class="dropdown-item" href="relatorio.php"><i class="bi bi-stickies me-2"></i>Painel de Passes</a></li>
                            <li><a class="dropdown-item" href="atestados.php"><i class="bi bi-file-medical me-2"></i>Atestados Médicos</a></li>
                            <?php if ($perfil_atual === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#"><i class="bi bi-graph-up-arrow me-2"></i>Relatórios</a>
                                    <ul class="dropdown-menu shadow-sm">
                                        <li><a class="dropdown-item" href="relatorio_pelotao.php"><i class="bi bi-people me-2"></i>Pelotão</a></li>
                                        <li><a class="dropdown-item" href="relatorio_analitico.php"><i class="bi bi-search me-2"></i>Analítico</a></li>
                                        <li><a class="dropdown-item" href="relatorio_sintetico.php"><i class="bi bi-calculator me-2"></i>Sintético</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-success" href="exportar_excel.php"><i class="bi bi-file-earmark-excel me-2"></i>Exportar Excel</a></li>
                                    </ul>
                                </li>
                                <li><a class="dropdown-item" href="turmas.php"><i class="bi bi-building me-2"></i>Gerenciar Turmas</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-nav ms-auto gap-2">
                <form method="post" action="index.php">
                    <button type="submit" name="finaliza" class="btn btn-sm btn-outline-danger bi bi-door-closed"> Sair do Sistema</button>
                </form>
            </div>
        </div>
    </div>
</nav>