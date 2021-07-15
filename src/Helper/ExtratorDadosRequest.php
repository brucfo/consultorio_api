<?php


namespace App\Helper;


use Symfony\Component\HttpFoundation\Request;

class ExtratorDadosRequest
{

    private function buscaDadosRequest(Request $request)
    {
        $queryString = $request->query->all();
        $orderInfo   = $request->query->all('sort');

        $paginaAtual    = array_key_exists('page', $queryString)
            ? $queryString['page']
            : 1;
        $itensPorPagina = array_key_exists('itensPorPagina', $queryString)
            ? $queryString['itensPorPagina']
            : 2;

        unset($queryString['sort']);
        unset($queryString['page']);
        unset($queryString['itensPorPagina']);

        return [$orderInfo, $queryString, $paginaAtual, $itensPorPagina];
    }

    public function buscaDadosOrdenacao(Request $request)
    {
        [$orderInfo,] = $this->buscaDadosRequest($request);

        return $orderInfo;
    }

    public function buscaDadosFiltro(Request $request)
    {
        [, $filterInfo] = $this->buscaDadosRequest($request);

        return $filterInfo;
    }

    public function buscaDadosPaginado(Request $request)
    {
        [
            ,
            ,
            $paginaAtual,
            $itensPorPagina,
        ] = $this->buscaDadosRequest($request);

        return [$paginaAtual, $itensPorPagina];
    }

}