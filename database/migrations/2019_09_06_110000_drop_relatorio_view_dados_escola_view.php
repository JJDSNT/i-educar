<?php

use App\Support\Database\AsView;
use Illuminate\Database\Migrations\Migration;

class DropRelatorioViewDadosEscolaView extends Migration
{
    use AsView;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->dropView('relatorio.view_dados_escola');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropView('relatorio.view_dados_escola');
        $this->createView('relatorio.view_dados_escola', '2019-09-06');
    }
}