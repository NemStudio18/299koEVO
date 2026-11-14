<?php

namespace PellEditor\Controllers;

use Core\Auth\CurrentUser;
use Core\Security\Csrf;
use Utils\Util;
use Core\Core;

class PellEditorController
{
    /**
     * Endpoint d'exportation (PDF via mPDF, DOCX via PHPWord, Markdown, HTML)
     */
    public function export()
    {
        $user = CurrentUser::get();
        if (!$user || !$user->isLogged()) {
            header('HTTP/1.1 403 Forbidden');
            exit('Unauthorized');
        }

        require_once dirname(__DIR__) . '/libs/export_bootstrap.php';

        $format = $_POST['format'] ?? 'html';
        $html = $_POST['html'] ?? '';
        $title = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $_POST['title'] ?? 'Document') ?: 'Document';

        switch ($format) {
            case 'pdf':
                $mpdf = \MpdfService\MpdfService::getInstance();
                $mpdf->WriteHTML($html);
                $mpdf->Output($title . '.pdf', 'D');
                break;

            case 'docx':
                $phpWord = \PhpWordService\PhpWordService::getInstance();
                $section = $phpWord->addSection();
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                header("Content-Description: File Transfer");
                header('Content-Disposition: attachment; filename="' . $title . '.docx"');
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Transfer-Encoding: binary');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');
                $objWriter->save("php://output");
                break;

            case 'md':
                $md = $html;
                $md = preg_replace('/<(h[1-6])>(.*?)<\/\1>/i', "\n\n# $2\n\n", $md);
                $md = str_replace(['<h1>', '<h2>', '<h3>'], ["# ", "## ", "### "], $md);
                $md = str_replace(['</h1>', '</h2>', '</h3>', '</p>', '</div>'], "\n\n", $md);
                $md = str_replace(['<strong>', '</b>', '<b>'], '**', $md);
                $md = str_replace(['<em>', '</i>', '<i>'], '_', $md);
                $md = str_replace(['<br>', '<br/>', '<br />'], "\n", $md);

                header('Content-Type: text/markdown; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $title . '.md"');
                echo trim(strip_tags($md));
                break;

            default:
                header('Content-Type: text/html; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $title . '.html"');
                echo $html;
                break;
        }
        exit;
    }

    /**
     * Endpoint JSON pour l'éditeur Pell
     */
    public function gallery()
    {
        $user = CurrentUser::get();
        if (!$user || !$user->isLogged()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $images = [];
        $baseUrl = rtrim(Core::getInstance()->getConfigVal('siteUrl'), '/') . '/' . str_replace('./', '', UPLOAD) . 'galerie/';

        if (class_exists('\GaleriesManager')) {
            $gm = new \GaleriesManager();
            foreach ($gm->getItems() as $item) {
                $img = method_exists($item, 'getImg') ? $item->getImg() : ($item->attributes['img'] ?? '');
                if ($img !== '') {
                    $images[] = [
                        'id'    => method_exists($item, 'getId') ? $item->getId() : ($item->attributes['id'] ?? ''),
                        'title' => (method_exists($item, 'getTitle') ? $item->getTitle() : ($item->attributes['title'] ?? '')) ?: 'Sans titre',
                        'url'   => $baseUrl . $img
                    ];
                }
            }
        } else {
            $galleryFile = DATA_PLUGIN . 'galerie/galerie.json';
            if (file_exists($galleryFile)) {
                $data = Util::readJsonFile($galleryFile, true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (isset($item['img']) && $item['img'] !== '') {
                            $images[] = [
                                'id'    => $item['id'] ?? '',
                                'title' => $item['title'] ?? 'Sans titre',
                                'url'   => $baseUrl . $item['img']
                            ];
                        }
                    }
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($images);
        exit;
    }
}
