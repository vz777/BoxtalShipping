<?php

namespace BoxtalShipping\Controller\Admin;

use BoxtalShipping\Form\ExportPricing;
use BoxtalShipping\Service\BoxtalPricingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;

class ExportController extends BaseAdminController
{
    /**
     * @Route("/admin/module/boxtalshipping/export", name="boxtal.export_pricing", methods={"POST"})
     */
    public function exportPricingAction(Request $request, BoxtalPricingService $pricingService)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BoxtalShipping', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ExportPricing::getName());
        $pricingData = $pricingService->getGlobalPricingData();

        try {
            $vForm = $this->validateForm($form);
            $exportType = $vForm->get('export_type')->getData();

            if ($exportType === 'csv') {
                $content = $this->generateCsv($pricingData);
                $contentType = 'text/csv';
                $filename = 'boxtal_pricing.csv';
            } elseif ($exportType === 'json') {
                $content = json_encode($pricingData, JSON_PRETTY_PRINT);
                $contentType = 'application/json';
                $filename = 'boxtal_pricing.json';
            } else {
                throw new \Exception("No export type selected");
            }

            $response = new Response($content);
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            return $response;
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }


    // cette methode est a remettre en fonctionement elle permet d'avoir une autre version du csv
    /*
    private function generateCsv($jsonData)
    {
        $data = json_decode($jsonData, true);
        if (!is_array($data) || empty($data)) {
            throw new \InvalidArgumentException('Data must be a non-empty JSON string');
        }
    
        $output = fopen('php://temp', 'r+');
        
        // Headers
        $headers = ['Carrier', 'Service', 'Weight', 'Price'];
        fputcsv($output, $headers);
        
        // Data
        foreach ($data as $carrier => $services) {
            foreach ($services as $service => $weights) {
                foreach ($weights as $weight => $price) {
                    fputcsv($output, [$carrier, $service, $weight, $price]);
                }
            }
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
        
        
    }*/
    private function generateCsv($jsonData)
    {
        $data = json_decode($jsonData, true);
        if (!is_array($data) || empty($data)) {
            throw new \InvalidArgumentException('Data must be a non-empty JSON string');
        }

        $output = fopen('php://temp', 'r+');

        // Prepare headers and data structure
        $headers = ['Weight'];
        $rows = [];
        $weights = [];

        foreach ($data as $carrier => $services) {
            foreach ($services as $service => $weightPrices) {
                $headers[] = "$carrier - $service";
                foreach ($weightPrices as $weight => $price) {
                    $weights[$weight] = $weight;
                    $rows[$weight][] = $price;
                }
            }
        }

        // Sort weights
        ksort($weights);

        // Write headers
        fputcsv($output, $headers);

        // Write data rows
        foreach ($weights as $weight) {
            $row = [$weight];
            if (isset($rows[$weight])) {
                $row = array_merge($row, $rows[$weight]);
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }
}
