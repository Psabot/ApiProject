<?php

namespace MTI\LeBonCoinBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use MTI\LeBonCoinBundle\Tools\CheckUserCall;

class DefaultController extends Controller
{

    public function indexAction($name)
    {
        $check = new CheckUserCall();
        $check->test();
        echo($check->osef);
        return $this->render('MTILeBonCoinBundle:Default:index.html.twig', array('name' => $name));
    }

    public function offersAction(Request $request)
    {
        $regions_map = array("alsace","aquitaine","auvergne","basse_normandie","bourgogne", "bretagne", "centre", "champagne_ardenne", "corse", "franche_comte", "haute_normandie", "ile_de_france", "languedoc_roussillon", "limousin", "lorraine", "midi_pyrenees", "nord_pas_de_calais", "pays_de_la_loire", "picardie", "poitou_charentes", "provence_alpes_cote_d_azur", "rhone_alpes", "guadeloupe", "martinique", "guyane", "reunion");
        $categories_map = array("offres_d_emploi", "voitures", "motos", "caravaning", "utilitaires", "equipement_auto", "equipement_moto", "equipement_caravaning", "nautisme", "equipement_nautisme", "ventes_immobilieres", "locations", "colocations", "bureaux_commerces", "locations_gites", "chambres_d_hotes", "campings", "hotels", "hebergements_insolites", "informatique", "consoles_jeux_video", "image_son", "telephonie", "ameublement", "electromenager", "arts_de_la_table", "decoration", "linge_de_maison", "bricolage", "jardinage", "vetements", "chaussures", "accessoires_bagagerie", "montres_bijoux", "equipement_bebe", "dvd_films", "cd_musique", "livres", "animaux", "velos", "sports_hobbies", "instruments_de_musique", "collection", "jeux_jouets", "vins_gastronomie", "materiel_agricole", "transport_manutention", "btp_chantier_gros_oeuvre", "outillage_materiaux_2nd_oeuvre", "equipements_industriels", "restauration_hotellerie", "fournitures_de_bureau", "commerces_marches", "materiel_medical", "prestations_de_services", "billetterie", "evenements", "cours_particuliers", "covoiturage", "autres");

        $region_url = (!in_array($request->query->get('region'), $regions_map)) ? "ile_de_france" : $request->query->get('region');
        $category_url = (!in_array($request->query->get('category'), $categories_map)) ? "annonces" : $request->query->get('category');
        $towns_url = $request->query->get('towns');
        $type_url = $request->query->get('type');
        $query_url = $request->query->get('query');
        if ($type_url != null && $type_url == 'ind') $type_url = 'p';
        else if ($type_url != null && $type_url == 'pro') $type_url = 'c';
        else $type_url = 'a';
        $sort_url = ($request->query->get('sort') == "price") ? 1 : 0;
        $request_url = 'http://www.leboncoin.fr/'.$category_url.'/offres/'.$region_url."/?";
        if ($towns_url != null) $request_url .= "&location=".$towns_url;
        if ($sort_url != null) $request_url .= "&sp=".$sort_url;
        if ($type_url != null) $request_url .= "&f=".$type_url;
        if ($query_url != null) $request_url .= "&q=".$query_url;
        //echo $request_url;
        $html = file_get_html($request_url);

        if ($html->find('h2[id=result_ad_not_found]', 0) != null) {
            $response = new Response();
            $response->setContent("No results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        else if ($html->find('h1[id=result_ad_not_found_proaccount]', 0) != null) {
            $response = new Response();
            $response->setContent("No professional results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $onglet_all = $html->find('ul[class=navlist type]', 0)->find('li', 0);
        $onglet_part = $html->find('ul[class=navlist type]', 0)->find('li', 1);
        $onglet_pro = $html->find('ul[class=navlist type]', 0)->find('li', 2);
        $nb_individual = $nb_professional = null;
        if ($onglet_all->class == "selected") {
            $nb_total = $onglet_all->find('b', 0);
            if ($nb_total != null) {
                $nb_total = $nb_total->plaintext;
                $nb_curr = $onglet_all->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_total = $onglet_all->find('span[class=value]', 0)->plaintext;
                $nb_curr = null;
            }
            $nb_individual = $onglet_part;
            if ($nb_individual != null) $nb_individual = $nb_individual->find('span[class=value]', 0)->plaintext;
            $nb_professional = $onglet_pro;
            if ($nb_professional != null) $nb_professional = $nb_professional->find('span[class=value]', 0)->plaintext;
        }
        else if ($onglet_part != null && $onglet_part->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_part->find('b', 0) != null) {
                $nb_curr = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('b', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }
        else if ($onglet_pro != null && $onglet_pro->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_pro->find('b', 0) != null) {
                $nb_curr = $onglet_pro->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('b', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }


        $curr_page = $html->find('ul[id=paging]', 0);
        if ($curr_page != null) $curr_page = $curr_page->find('li[class=selected]', 0)->plaintext;
        $articles = array();

        foreach($html->find('div[class=list-lbc]', 0)->find('a') as $element) {
            $article_id = explode('.htm', $element->href);
            $article_id = explode('/', $article_id[0]);
            $article_id = $article_id[count($article_id) - 1];
            $locations = explode('/', utf8_encode(trim($element->find('div[class=placement]', 0)->plaintext)));
            if (count($locations) > 1) {
                $town = trim($locations[0]);
                $region = trim($locations[1]);
            }
            else {
                $town = 'all';
                $region = trim($locations[0]);
            }
            $price = $element->find('div[class=price]', 0);
            if ($price != null) $price = preg_replace('/[^0-9]/', '', $price->plaintext);
            $image = $element->find('img', 0);
            $nb_images = 0;
            if ($image != null) {
                $image = $image->src;
                $nb_images = $element->find('div[class=image-and-nb]', 0)->find('div[class=nb]', 0)->find('div[class=value radius]', 0)->plaintext;
            }
            $category = trim($element->find('div[class=category]', 0)->plaintext);

            $article = array(
                'ref' => $article_id,
                'url' => $element->href,
                'title' => $element->title,
                'category' => ($category == "") ? $category_url : $category,
                'region' => $region,
                'town' => $town,
                'price' => $price,
                'image' => $image,
                'nb_images' => $nb_images,
                'date' => $element->find('div[class=date]', 0)->find('div', 0)->plaintext." ".$element->find('div[class=date]', 0)->find('div', 1)->plaintext
            );
            array_push($articles, $article);
        }

        $response = new Response();
        $response_json = json_encode(array(
            'page' => ($curr_page == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $curr_page),
            'current' => ($nb_curr == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', explode('de', $nb_curr)[0]),
            'total' => preg_replace('/[\sa-zA-Z]+/', '', $nb_total),
            'individual' => ($nb_individual == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_individual),
            'professional' => ($nb_professional == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_professional),
            'ads' => 'offers',
            'type' => ($type_url == 'p') ? 'ind' : (($type_url == 'c') ? 'pro' : 'all'),
            'region' => $region_url,
            'town' => $towns_url,
            'category' => $category_url,
            'sort' => ($sort_url == 1) ? 'price' : 'date',
            'query' => ($query_url == null) ? null : $query_url,
            'articles' => $articles

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function demandsAction(Request $request)
    {
        $regions_map = array("alsace","aquitaine","auvergne","basse_normandie","bourgogne", "bretagne", "centre", "champagne_ardenne", "corse", "franche_comte", "haute_normandie", "ile_de_france", "languedoc_roussillon", "limousin", "lorraine", "midi_pyrenees", "nord_pas_de_calais", "pays_de_la_loire", "picardie", "poitou_charentes", "provence_alpes_cote_d_azur", "rhone_alpes", "guadeloupe", "martinique", "guyane", "reunion");
        $categories_map = array("offres_d_emploi", "voitures", "motos", "caravaning", "utilitaires", "equipement_auto", "equipement_moto", "equipement_caravaning", "nautisme", "equipement_nautisme", "ventes_immobilieres", "locations", "colocations", "bureaux_commerces", "locations_gites", "chambres_d_hotes", "campings", "hotels", "hebergements_insolites", "informatique", "consoles_jeux_video", "image_son", "telephonie", "ameublement", "electromenager", "arts_de_la_table", "decoration", "linge_de_maison", "bricolage", "jardinage", "vetements", "chaussures", "accessoires_bagagerie", "montres_bijoux", "equipement_bebe", "dvd_films", "cd_musique", "livres", "animaux", "velos", "sports_hobbies", "instruments_de_musique", "collection", "jeux_jouets", "vins_gastronomie", "materiel_agricole", "transport_manutention", "btp_chantier_gros_oeuvre", "outillage_materiaux_2nd_oeuvre", "equipements_industriels", "restauration_hotellerie", "fournitures_de_bureau", "commerces_marches", "materiel_medical", "prestations_de_services", "billetterie", "evenements", "cours_particuliers", "covoiturage", "autres");

        $region_url = (!in_array($request->query->get('region'), $regions_map)) ? "ile_de_france" : $request->query->get('region');
        $category_url = (!in_array($request->query->get('category'), $categories_map)) ? "annonces" : $request->query->get('category');
        $towns_url = $request->query->get('towns');
        $type_url = $request->query->get('type');
        $query_url = $request->query->get('query');
        if ($type_url != null && $type_url == 'ind') $type_url = 'p';
        else if ($type_url != null && $type_url == 'pro') $type_url = 'c';
        else $type_url = 'a';
        $sort_url = ($request->query->get('sort') == "price") ? 1 : 0;
        $request_url = 'http://www.leboncoin.fr/'.$category_url.'/demandes/'.$region_url."/?";
        if ($towns_url != null) $request_url .= "&location=".$towns_url;
        if ($sort_url != null) $request_url .= "&sp=".$sort_url;
        if ($type_url != null) $request_url .= "&f=".$type_url;
        if ($query_url != null) $request_url .= "&q=".$query_url;
        //echo $request_url;
        $html = file_get_html($request_url);

        if ($html->find('h2[id=result_ad_not_found]', 0) != null) {
            $response = new Response();
            $response->setContent("No results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        else if ($html->find('h1[id=result_ad_not_found_proaccount]', 0) != null) {
            $response = new Response();
            $response->setContent("No professional results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $onglet_all = $html->find('ul[class=navlist type]', 0)->find('li', 0);
        $onglet_part = $html->find('ul[class=navlist type]', 0)->find('li', 1);
        $onglet_pro = $html->find('ul[class=navlist type]', 0)->find('li', 2);
        $nb_individual = $nb_professional = null;
        if ($onglet_all->class == "selected") {
            $nb_total = $onglet_all->find('b', 0);
            if ($nb_total != null) {
                $nb_total = $nb_total->plaintext;
                $nb_curr = $onglet_all->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_total = $onglet_all->find('span[class=value]', 0)->plaintext;
                $nb_curr = null;
            }
            $nb_individual = $onglet_part;
            if ($nb_individual != null) $nb_individual = $nb_individual->find('span[class=value]', 0)->plaintext;
            $nb_professional = $onglet_pro;
            if ($nb_professional != null) $nb_professional = $nb_professional->find('span[class=value]', 0)->plaintext;
        }
        else if ($onglet_part != null && $onglet_part->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_part->find('b', 0) != null) {
                $nb_curr = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('b', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }
        else if ($onglet_pro != null && $onglet_pro->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_pro->find('b', 0) != null) {
                $nb_curr = $onglet_pro->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('b', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }


        $curr_page = $html->find('ul[id=paging]', 0);
        if ($curr_page != null) $curr_page = $curr_page->find('li[class=selected]', 0)->plaintext;
        $articles = array();

        foreach($html->find('div[class=list-lbc]', 0)->find('a') as $element) {
            $article_id = explode('.htm', $element->href);
            $article_id = explode('/', $article_id[0]);
            $article_id = $article_id[count($article_id) - 1];
            $locations = explode('/', utf8_encode(trim($element->find('div[class=placement]', 0)->plaintext)));
            if (count($locations) > 1) {
                $town = trim($locations[0]);
                $region = trim($locations[1]);
            }
            else {
                $town = 'all';
                $region = trim($locations[0]);
            }
            $price = $element->find('div[class=price]', 0);
            if ($price != null) $price = preg_replace('/[^0-9]/', '', $price->plaintext);
            $image = $element->find('img', 0);
            $nb_images = 0;
            if ($image != null) {
                $image = $image->src;
                $nb_images = $element->find('div[class=image-and-nb]', 0)->find('div[class=nb]', 0)->find('div[class=value radius]', 0)->plaintext;
            }
            $category = trim($element->find('div[class=category]', 0)->plaintext);

            $article = array(
                'ref' => $article_id,
                'url' => $element->href,
                'title' => $element->title,
                'category' => ($category == "") ? $category_url : $category,
                'region' => $region,
                'town' => $town,
                'price' => $price,
                'image' => $image,
                'nb_images' => $nb_images,
                'date' => $element->find('div[class=date]', 0)->find('div', 0)->plaintext." ".$element->find('div[class=date]', 0)->find('div', 1)->plaintext
            );
            array_push($articles, $article);
        }

        $response = new Response();
        $response_json = json_encode(array(
            'page' => ($curr_page == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $curr_page),
            'current' => ($nb_curr == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', explode('de', $nb_curr)[0]),
            'total' => preg_replace('/[\sa-zA-Z]+/', '', $nb_total),
            'individual' => ($nb_individual == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_individual),
            'professional' => ($nb_professional == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_professional),
            'ads' => 'offers',
            'type' => ($type_url == 'p') ? 'ind' : (($type_url == 'c') ? 'pro' : 'all'),
            'region' => $region_url,
            'town' => $towns_url,
            'category' => $category_url,
            'sort' => ($sort_url == 1) ? 'price' : 'date',
            'query' => ($query_url == null) ? null : $query_url,
            'articles' => $articles

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function adsAction(Request $request, $adID)
    {
        /*$request_url = 'http://www.leboncoin.fr/annonces/'.$region_url."/?";
        if ($towns_url != null) $request_url .= "&location=".$towns_url;
        if ($sort_url != null) $request_url .= "&sp=".$sort_url;
        if ($type_url != null) $request_url .= "&f=".$type_url;
        if ($query_url != null) $request_url .= "&q=".$query_url;
        //echo $request_url;
        $html = file_get_html($request_url);

        if ($html->find('h2[id=result_ad_not_found]', 0) != null) {
            $response = new Response();
            $response->setContent("No results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        else if ($html->find('h1[id=result_ad_not_found_proaccount]', 0) != null) {
            $response = new Response();
            $response->setContent("No professional results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $onglet_all = $html->find('ul[class=navlist type]', 0)->find('li', 0);
        $onglet_part = $html->find('ul[class=navlist type]', 0)->find('li', 1);
        $onglet_pro = $html->find('ul[class=navlist type]', 0)->find('li', 2);
        $nb_individual = $nb_professional = null;
        if ($onglet_all->class == "selected") {
            $nb_total = $onglet_all->find('b', 0);
            if ($nb_total != null) {
                $nb_total = $nb_total->plaintext;
                $nb_curr = $onglet_all->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_total = $onglet_all->find('span[class=value]', 0)->plaintext;
                $nb_curr = null;
            }
            $nb_individual = $onglet_part;
            if ($nb_individual != null) $nb_individual = $nb_individual->find('span[class=value]', 0)->plaintext;
            $nb_professional = $onglet_pro;
            if ($nb_professional != null) $nb_professional = $nb_professional->find('span[class=value]', 0)->plaintext;
        }
        else if ($onglet_part != null && $onglet_part->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_part->find('b', 0) != null) {
                $nb_curr = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('b', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }
        else if ($onglet_pro != null && $onglet_pro->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_pro->find('b', 0) != null) {
                $nb_curr = $onglet_pro->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('b', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }


        $curr_page = $html->find('ul[id=paging]', 0);
        if ($curr_page != null) $curr_page = $curr_page->find('li[class=selected]', 0)->plaintext;
        $articles = array();

        foreach($html->find('div[class=list-lbc]', 0)->find('a') as $element) {
            $article_id = explode('.htm', $element->href);
            $article_id = explode('/', $article_id[0]);
            $article_id = $article_id[count($article_id) - 1];
            $locations = explode('/', utf8_encode(trim($element->find('div[class=placement]', 0)->plaintext)));
            if (count($locations) > 1) {
                $town = trim($locations[0]);
                $region = trim($locations[1]);
            }
            else {
                $town = 'all';
                $region = trim($locations[0]);
            }
            $price = $element->find('div[class=price]', 0);
            if ($price != null) $price = preg_replace('/[^0-9]/', '', $price->plaintext);
            $image = $element->find('img', 0);
            $nb_images = 0;
            if ($image != null) {
                $image = $image->src;
                $nb_images = $element->find('div[class=image-and-nb]', 0)->find('div[class=nb]', 0)->find('div[class=value radius]', 0)->plaintext;
            }
            $category = trim($element->find('div[class=category]', 0)->plaintext);

            $article = array(
                'ref' => $article_id,
                'url' => $element->href,
                'title' => $element->title,
                'category' => ($category == "") ? $category_url : $category,
                'region' => $region,
                'town' => $town,
                'price' => $price,
                'image' => $image,
                'nb_images' => $nb_images,
                'date' => $element->find('div[class=date]', 0)->find('div', 0)->plaintext." ".$element->find('div[class=date]', 0)->find('div', 1)->plaintext
            );
            array_push($articles, $article);
        }

        $response = new Response();
        $response_json = json_encode(array(
            'page' => ($curr_page == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $curr_page),
            'current' => ($nb_curr == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', explode('de', $nb_curr)[0]),
            'total' => preg_replace('/[\sa-zA-Z]+/', '', $nb_total),
            'individual' => ($nb_individual == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_individual),
            'professional' => ($nb_professional == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_professional),
            'ads' => 'offers',
            'type' => ($type_url == 'p') ? 'ind' : (($type_url == 'c') ? 'pro' : 'all'),
            'region' => $region_url,
            'town' => $towns_url,
            'category' => $category_url,
            'sort' => ($sort_url == 1) ? 'price' : 'date',
            'query' => ($query_url == null) ? null : $query_url,
            'articles' => $articles

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);

        $response->headers->set('Content-Type', 'application/json');
        return $response;*/
    }
}
