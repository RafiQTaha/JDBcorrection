<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DatatablesController extends AbstractController {

    /**
     * Pull a particular property from each assoc. array in a numeric array, 
     * returning and array of the property values from each item.
     *
     *  @param  array  $a    Array to get data from
     *  @param  string $prop Property to read
     *  @return array        Array of property values
     */
    static function Pluck($a, $prop) {
        $out = array();
        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $out[] = $a[$i][$prop];
        }
        return $out;
    }

    /*
     * search
     *      
     */

    static function Search($request, $columns) {
        $where = "";
        $params = $request->query;
        // dd($params);
        if (!empty($params->all('search')['value'])) {
            $search = trim($params->get('search')['value']);
            foreach ($columns as $key => $value) {
                if ($key == 0) {
                    $where = "and (" . $value['db'] . " LIKE '%$search%' ";
                } else {
                    $where .= " OR " . $value['db'] . " LIKE '%$search%' ";
                }
            }
            $where .= " )";
        }
        return $where;
    }

    /*
     * search
     *      
     */

    static function Order($request, $columns) {
        $params = $request->query;
        // dd($params);
        $sqlRequest = "";
        $sqlRequest = " ORDER BY " . self::Pluck($columns, 'db')[$params->all('order')[0]['column']] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";
        return $sqlRequest;
    }
    
    
    
    
    

}
