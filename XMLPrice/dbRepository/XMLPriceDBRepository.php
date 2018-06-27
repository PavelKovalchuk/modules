<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 21.12.2017
 * Time: 11:17
 */

class XMLPriceDBRepository
{

    public function getCategoriesFromDb( XMLPriceDBCategoriesRepositoryAbstract $request)
    {

        $sql = $request->getCategoriesSQlRequest();

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[] = $row;
        }

        return $result;

    }

    public function getAdditionalPicturesFromDb($prIdsStr)
    {

        $sql = "SELECT
                products_id,
                image_id,
                image_nr,
                image_name
            FROM products_images
            WHERE
                products_id IN ({$prIdsStr})
            ORDER BY products_id";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[$row['products_id']][] = $row;
        }

        return $result;

    }

    public function getExtraParamsFromDb($prIdsStr)
    {

        $sql = "SELECT
                ptpef.products_id,
                pef.products_extra_fields_name name,
                ptpef.products_extra_fields_value value,                
                pef.products_extra_fields_status AS status
            FROM products_to_products_extra_fields ptpef
            JOIN products_extra_fields AS pef
              ON  ptpef.products_extra_fields_id = pef.products_extra_fields_id
            WHERE 1
              AND pef.products_extra_fields_name != 'Название'
              AND ptpef.products_id IN ({$prIdsStr})
              AND pef.products_extra_fields_status = '1'
              AND pef.languages_id = '1'
            ORDER BY ptpef.products_id, pef.products_extra_fields_order";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[$row['products_id']][$row['name']] = $row;
        }

        return $result;

    }

    public function getOffersFromDb( XMLPriceDBOffersRepositoryAbstract $request )
    {
        $sql = $request->getOffersSQlRequest();

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[] = $row;
        }

        return $result;

    }

    public function getRussianNames($prIdsStr)
    {
        $sql = "SELECT p.products_id AS id
                         ,ptpef.products_extra_fields_value as 'russian_name'   
                                                                     
                        FROM products p
                        
                        JOIN products_to_products_extra_fields AS ptpef
                        ON (p.products_id = ptpef.products_id )
                   
                        WHERE
                        ptpef.products_id IN ({$prIdsStr})
                        AND ptpef.products_extra_fields_id = 3                        
                        
                        GROUP BY p.products_id 
                        ORDER BY p.products_id ASC";


        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[$row['id']] = $row;
        }

        return $result;

    }

}