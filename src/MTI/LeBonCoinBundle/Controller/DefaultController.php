<?php

namespace MTI\LeBonCoinBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use MTI\LeBonCoinBundle\Tools\ParsingTools;

class DefaultController extends Controller
{

    public function indexAction($name)
    {
        $check = new CheckUserCall($this->getDoctrine());
        var_dump($check->check());
        return $this->render('MTILeBonCoinBundle:Default:index.html.twig', array('name' => $name));
    }

    public function offersAction(Request $request)
    {
        $region_url = (!in_array($request->query->get('region'), ParsingTools::$regions_map)) ? "ile_de_france" : $request->query->get('region');
        $category_url = (!in_array($request->query->get('category'), ParsingTools::$categories_map)) ? "annonces" : $request->query->get('category');
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
                'title' => utf8_encode($element->title),
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
        $region_url = (!in_array($request->query->get('region'), ParsingTools::$regions_map)) ? "ile_de_france" : $request->query->get('region');
        $category_url = (!in_array($request->query->get('category'), ParsingTools::$categories_map)) ? "annonces" : $request->query->get('category');
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
                'title' => utf8_encode($element->title),
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
            'ads' => 'demands',
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
        $request_url = 'http://www.leboncoin.fr/annonces/'.$adID.".htm";
        //echo $request_url;
        try {
            $html = file_get_html($request_url);
        } catch (Exception $e) {
            $response = new Response();
            $response->setContent("No results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        if ($html->find('div[class=lbcContainer]', 0) == null) {
            $response = new Response();
            $response->setContent("No results found");
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $price = $html->find('span[itemprop=price]', 0);
        if ($price != null) $price = $price->content;
        $date = $html->find('div[class=upload_by]', 0)->plaintext;
        $date = explode('Mise en ligne le ', $date);
        $date = explode(' &agrave; ', $date[1]);
        $date = $date[0].' '.explode('.', $date[1])[0];
        $ads = 'offers';
        if ($html->find('ul[id=nav_main]', 0)->find('li', 3)->class == 'demande selected') $ads = 'demands';
        $type = $html->find('div[class=upload_by]', 0)->plaintext;
        if (strpos($type,'Pro ') !== false) $type = 'pro';
        else $type = 'ind';
        $image = $html->find('a[id=image]', 0);
        if ($image != null) {
            $image = explode('url(\'', $image->style)[1];
            $image = explode('\');', $image)[0];
            $nb_images = $html->find('div[class=thumbs_carousel_window]', 0);
            if ($nb_images == null) {
                $nb_images = 1;
                $thumbs = array();
            }
            else {
                $images_thumbs = $html->find('div[class=thumbs_carousel_window]', 0)->find('span[class=thumbs]');
                $nb_images = count($images_thumbs);
                $thumbs = array();
                foreach($images_thumbs as $element) {
                    $thumb = explode('url(\'', $element->style)[1];
                    $thumb = explode('\');', $thumb)[0];
                    array_push($thumbs, $thumb);
                }
            }
        }
        else {
            $nb_images = 0;
            $thumbs = array();
        }

        $response = new Response();
        $response_json = json_encode(array(
            'ref' => $adID,
            'ads' => $ads,
            'type' => $type,
            'user' => utf8_encode($html->find('div[class=upload_by]', 0)->find('a', 0)->plaintext),
            'region' => $html->find('span[class=fine_print]', 0)->find('a', 1)->plaintext,
            'town' => $html->find('td[itemprop=addressLocality]', 0)->plaintext,
            'postal' => $html->find('td[itemprop=postalCode]', 0)->plaintext,
            'category' => $html->find('span[class=fine_print]', 0)->find('a', 2)->plaintext,
            'title' => utf8_encode($html->find('h1[id=ad_subject]', 0)->plaintext),
            'price' => $price,
            'description' => utf8_encode($html->find('div[itemprop=description]', 0)->plaintext),
            'date' => $date,
            'image' => $image,
            'nb_images' => $nb_images,
            'thumbs' => $thumbs

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
