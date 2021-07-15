<?php


namespace App\Helper;


use App\Entity\Especialidade;

class EspecialidadeFactory implements EntidadeFactory
{

    public function criarEntidade(string $json): Especialidade
    {
        $dadosEmJson = json_decode($json);
        if (!property_exists($dadosEmJson, 'descricao')) {
            throw new EntityFactoryException(
                'Especialidade precisa de Descrição'
            );
        }
        $especialidade = new Especialidade();
        $especialidade->setDescricao($dadosEmJson->descricao);

        return $especialidade;
    }

}