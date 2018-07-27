<?php

/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Prefeitura Municipal de Itajaí <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Include
 * @since     Arquivo disponível desde a versão 1.0.0
 * @version   $Id$
 */

require_once 'include/clsBanco.inc.php';

/**
 * clsMenu class.
 *
 * @author    Prefeitura Municipal de Itajaí <ctima@itajai.sc.gov.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Include
 * @since     Classe disponível desde a versão 1.0.0
 * @version   @@package_version@@
 */
class clsMenu
{
  var $aberto;

  function MakeMenu($linhaTemplate, $categoriaTemplate)
  {
    // Usa helper de Url para pegar o path da requisição
    require_once 'CoreExt/View/Helper/UrlHelper.php';

    $uri = explode('/', CoreExt_View_Helper_UrlHelper::url($_SERVER['REQUEST_URI'],
      array(
        'components' => CoreExt_View_Helper_UrlHelper::URL_PATH
      )
    ));

    $this->aberto = 0;

    $saida                = '';
    $linha_nova           = $linhaTemplate;
    $linha_nova_subtitulo = $categoriaTemplate;
    $super_usuario        = '';

    $itens_menu      = array();
    $autorizado_menu = array();

    if (!isset($_SESSION)) {
      @session_start();
    }

    $id_usuario  = $_SESSION['id_pessoa'];
    $opcoes_menu = $_SESSION['menu_opt'];

    $dba = new clsBanco();
    $dba->Consulta('
      SELECT
        mtu.ref_cod_menu_submenu
      FROM
      pmieducar.menu_tipo_usuario mtu
      INNER JOIN pmieducar.tipo_usuario tu ON mtu.ref_cod_tipo_usuario = tu.cod_tipo_usuario
      INNER JOIN pmieducar.usuario u ON tu.cod_tipo_usuario = u.ref_cod_tipo_usuario
      WHERE  u.cod_usuario = ' . $id_usuario);

    while ($dba->ProximoRegistro()) {
      list($aut) = $dba->Tupla();
      $autorizado_menu[] = $aut;

      if ($aut == 0) {
        $super_usuario = TRUE;
      }
    }

    $strAutorizado = implode(', ', $autorizado_menu);

    if (isset($_SESSION['convidado'])) {
        $strAutorizado = '999999';
    }
    session_write_close();

    $db = new clsBanco();

    if ($strAutorizado == '0' || $super_usuario) {

      $suspenso = $_GET['suspenso'] ?? $_SESSION['suspenso'] ?? $_SESSION['tipo_menu'];

      if ($suspenso) {
        $sql = "
          SELECT
            pai.nm_menu,
            nome_menu.nm_menu AS nm_menu_pai,
            pai.title AS title_pai,
            sub.nm_submenu,
            sub.arquivo,
            sub.title,
            pai.cod_menu_menu,
            CASE
              WHEN pai.ref_cod_menu_pai IS NULL
                THEN 0
              ELSE
                1
            END AS ref_menu_pai,
            pai.caminho,
            pai.icon_class,
            pai.ord_menu
          FROM
            menu_menu AS pai LEFT OUTER JOIN
              menu_menu AS filho ON (filho.ref_cod_menu_pai = pai.cod_menu_menu AND pai.ref_cod_menu_pai = NULL),
            menu_submenu AS sub,
            menu_menu as nome_menu
          WHERE
            pai.ativo = TRUE
            AND nome_menu.cod_menu_menu = COALESCE(pai.ref_cod_menu_pai,pai.cod_menu_menu)
            AND sub.cod_sistema = '2'
            AND pai.cod_menu_menu = sub.ref_cod_menu_menu
          ORDER BY
            pai.ord_menu, upper(nome_menu.nm_menu), ref_menu_pai, UPPER(pai.nm_menu), sub.nm_submenu";
      }
      else {
        $sql ="
          SELECT
            DISTINCT pai.nm_menu,
            nome_menu.nm_menu AS nm_menu_pai,
            pai.title AS title_pai,
            sub.nm_submenu,
            sub.arquivo,
            sub.title,
            pai.cod_menu_menu,
            CASE
              WHEN pai.ref_cod_menu_pai IS NULL
                THEN 0
              ELSE
                1
            END AS ref_menu_pai,
            pai.caminho,
            pai.icon_class,
            pai.ord_menu,
            pai.ord_menu, UPPER(nome_menu.nm_menu), UPPER(pai.nm_menu), sub.nm_submenu
          FROM
            menu_menu AS pai LEFT OUTER JOIN
              menu_menu AS filho ON (filho.ref_cod_menu_pai = pai.cod_menu_menu),
            menu_submenu AS sub,
            menu_menu AS nome_menu
          WHERE
            pai.ativo = TRUE
            AND nome_menu.cod_menu_menu = COALESCE(pai.ref_cod_menu_pai, pai.cod_menu_menu)
            AND sub.cod_sistema = '2'
            AND pai.cod_menu_menu = sub.ref_cod_menu_menu
          ORDER BY
            pai.ord_menu, UPPER(nome_menu.nm_menu), ref_menu_pai, UPPER(pai.nm_menu), sub.nm_submenu";
      }
    }
    else {
      $query_lista = '';

      if ($strAutorizado) {
        $query_lista = "sub.cod_menu_submenu in ({$strAutorizado}) OR ";
      }

      $suspenso = '';

      if ($strAutorizado == '999999') {
        $sql ="
          SELECT
            pai.nm_menu,
            nome_menu.nm_menu AS nm_menu_pai,
            pai.title AS title_pai,
            sub.nm_submenu,
            sub.arquivo,
            sub.title,
            pai.cod_menu_menu,
            CASE
              WHEN pai.ref_cod_menu_pai IS NULL
                THEN 0
              ELSE
               1
            END AS ref_menu_pai,
            pai.caminho,
            pai.icon_class,
            pai.ord_menu
          FROM
            menu_menu AS pai LEFT OUTER JOIN menu_menu as filho ON (filho.ref_cod_menu_pai = pai.cod_menu_menu),
            menu_submenu AS sub,
            menu_menu AS nome_menu
          WHERE
            pai.ativo = TRUE
            AND nome_menu.cod_menu_menu = COALESCE(pai.ref_cod_menu_pai, pai.cod_menu_menu)
            AND sub.cod_sistema = '2'
            AND pai.cod_menu_menu = sub.ref_cod_menu_menu
            AND ($query_lista
              sub.cod_menu_submenu IN (
                SELECT
                  sub2.cod_menu_submenu
                FROM
                  menu_submenu sub2
                WHERE
                  sub2.nivel='1'
              )
            )
          ORDER BY
            pai.ord_menu, UPPER(nome_menu.nm_menu), ref_menu_pai, UPPER(pai.nm_menu), sub.nm_submenu";
      }
      else {
        $sql ="
          SELECT
            DISTINCT pai.nm_menu,
            nome_menu.nm_menu AS nm_menu_pai,
            pai.title AS title_pai,
            sub.nm_submenu,
            sub.arquivo,
            sub.title,
            pai.cod_menu_menu,
            CASE
              WHEN pai.ref_cod_menu_pai IS NULL
                THEN 0
              ELSE
                1
            END AS ref_menu_pai,
            pai.caminho,
            pai.icon_class,
            UPPER(nome_menu.nm_menu),
            UPPER(pai.nm_menu),
            pai.ord_menu
          FROM
            menu_menu AS pai
            LEFT OUTER JOIN menu_menu AS filho ON (filho.ref_cod_menu_pai = pai.cod_menu_menu),
            menu_submenu AS sub,
            menu_menu AS nome_menu
          WHERE
            pai.ativo = TRUE
            AND nome_menu.cod_menu_menu = COALESCE(pai.ref_cod_menu_pai, pai.cod_menu_menu)
            AND sub.cod_sistema = '2'
            AND pai.cod_menu_menu = sub.ref_cod_menu_menu
            AND ($query_lista
              sub.cod_menu_submenu IN (
                SELECT
                  sub2.cod_menu_submenu
                FROM
                  menu_submenu sub2
                WHERE
                  sub2.nivel='2')
            )
            AND EXISTS
              (SELECT *
                 FROM pmieducar.menu_tipo_usuario
                INNER JOIN pmieducar.usuario ON (usuario.ref_cod_tipo_usuario = menu_tipo_usuario.ref_cod_tipo_usuario)
                WHERE menu_tipo_usuario.ref_cod_menu_submenu = sub.cod_menu_submenu
                  AND usuario.cod_usuario = $id_usuario)
            $suspenso
          ORDER BY
            pai.ord_menu, UPPER(nome_menu.nm_menu), ref_menu_pai, UPPER(pai.nm_menu), sub.nm_submenu
        ";
      }
    }

    $db->Consulta($sql);

    while ($db->ProximoRegistro()) {
      list ($nome,$nomepai, $titlepai, $nomesub, $arquivo, $titlesub,
        $cod_submenu, $ref_menu_pai, $caminho, $icon) = $db->Tupla();

      $itens_menu[] = array($nome, $nomepai, $titlepai, $nomesub, $arquivo,
        $titlesub, $cod_submenu,$ref_menu_pai, $caminho, $icon);
    }

    $saida = '';
    $menuPaiAtual = '';

    foreach ($itens_menu as $item) {
      if ($item[0] != $menuPaiAtual) {
        $estilo_linha = 'nvp_sub';

        $this->aberto = 0;
        $menuPaiId = $item[6];

        if (isset($_COOKIE['menu_' . $menuPaiId])) {
          if ($_COOKIE['menu_' . $menuPaiId] == 'V') {
            $this->aberto = 1;
          }
        }

        // Define a ação para ser contrária ao status atual
        if ($this->aberto) {
          $imagem     = 'up2';
          $acao       = 0;
          $simbolo    = '-';
          $title_acao = 'Fechar a categoria';
        }
        else {
          $imagem     = 'down2';
          $acao       = 1;
          $simbolo    = '+';
          $title_acao = 'Abrir a categoria';
        }

        $submenus = '';

        $faIcon = empty($item[9]) ? '' : '<i class="fa '. $item[9] .'" aria-hidden="true"></i>';

        // Adiciona um menu pai
        $aux_temp = $linha_nova_subtitulo;
        $aux_temp = str_replace('<!-- #&NOME&# -->',       $item[0], $aux_temp);
        $aux_temp = str_replace('<!-- #&ALT&# -->',        $item[3], $aux_temp);
        $aux_temp = str_replace('<!-- #&ID&# -->',         $item[6], $aux_temp);
        $aux_temp = str_replace('<!-- #&CAMINHO&# -->',    $item[8], $aux_temp);
        $aux_temp = str_replace('<!-- #&FAICON&# -->',     $faIcon, $aux_temp);
        $aux_temp = str_replace('<!-- #&ACAO&# -->',       $acao, $aux_temp);
        $aux_temp = str_replace('<!-- #&SIMBOLO&# -->',    $simbolo, $aux_temp);
        $aux_temp = str_replace('<!-- #&TITLE_ACAO&# -->', $title_acao, $aux_temp);
        $aux_temp = str_replace('<!-- #&MENUPAI&# -->',    $item[0], $aux_temp);
        $aux_temp = str_replace('<!-- #&IMAGEM&# -->',     $imagem, $aux_temp);
        $aux_temp = str_replace('<!-- #&IDMENUPAI&# -->',  $menuPaiId, $aux_temp);

        $style = $this->aberto == 1 ? '' : 'style="display:none;"';

        $aux_temp = str_replace('<!-- #&STYLE&# -->', $style , $aux_temp);

        $saida .= $aux_temp;

        // Define que este é o menu pai atual
        $menuPaiAtual = $item[0];
      }

      $aux_temp = $linha_nova;
      if (substr($item[4], 0, 5) == 'http:') {
        $target = '_blank';
      }
      else {
        $target = '_top';
      }

      // Path do item de menu
      $path = $item[4];

      // Corrige o path usando caminhos relativos para permitir a inclusão
      // de itens no menu que apontem para um módulo
      if ($uri[1] == 'module') {
        if (0 === strpos($path, 'module')) {
          $path = '../../' . $path;
        }
        else {
          $path = '../../intranet/' . $path;
        }
      }
      elseif (0 === strpos($path, 'module')) {
        $path = '../../' . $path;
      }

      $aux_temp = str_replace('<!-- #&CLASS&# -->',  $estilo_linha, $aux_temp);
      $aux_temp = str_replace('<!-- #&NOME&# -->',   $item[3], $aux_temp);
      $aux_temp = str_replace('<!-- #&LINK&# -->',   $path, $aux_temp);
      $aux_temp = str_replace('<!-- #&ALT&# -->',    $item[3], $aux_temp);
      $aux_temp = str_replace('<!-- #&TARGET&# -->', $target, $aux_temp);
      $submenus .= $aux_temp;
    }

    $saida = str_replace('<!-- #&MENUS&# -->', $submenus, $saida);
    return $saida;
  }
}
