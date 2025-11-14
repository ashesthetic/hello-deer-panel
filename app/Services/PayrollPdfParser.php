<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PayrollPdfParser
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extract text from a PDF file.
     *
     * @param string $filePath
     * @return string
     */
    public function extractText($filePath)
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            throw new \Exception("Failed to extract text from PDF: " . $e->getMessage());
        }
    }

    /**
     * Extract text from each page of the PDF separately.
     *
     * @param string $filePath
     * @return array Array of page texts
     */
    public function extractTextByPages($filePath)
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
            $pages = $pdf->getPages();
            $pageTexts = [];
            
            foreach ($pages as $page) {
                $pageTexts[] = $page->getText();
            }
            
            return $pageTexts;
        } catch (\Exception $e) {
            throw new \Exception("Failed to extract text from PDF: " . $e->getMessage());
        }
    }

    /**
     * Extract employee name from address block (first line).
     *
     * @param string $text
     * @return string|null
     */
    protected function extractEmployeeName($text)
    {
        // Look for the name pattern - typically after some header text and before address
        // The name is usually the first line before "Unit-" or street address
        if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\s*\n\s*(?:Unit-|[0-9]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Alternative: look for lines that look like names (1-3 capitalized words)
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            // Match name pattern: Single name (Vansh) or First Last or First Middle Last
            if (preg_match('/^[A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2}$/', $line)) {
                return $line;
            }
        }
        
        return null;
    }

    /**
     * Parse payroll data from extracted text.
     *
     * @param string $text
     * @return array
     */
    public function parsePayrollData($text)
    {
        $data = [];

        // Extract employee name
        $data['employee_name'] = $this->extractEmployeeName($text);

        // Parse pay period
        if (preg_match('/Pay\s*Period[:\s]+([^\n]+)/i', $text, $matches)) {
            $data['pay_period'] = trim($matches[1]);
        }

        // Parse pay date
        if (preg_match('/Pay\s*Date[:\s]+([\d\/\-]+)/i', $text, $matches)) {
            $data['pay_date'] = trim($matches[1]);
        }

        // Parse payment date (deposit date)
        if (preg_match('/(?:Payment|Deposit)\s*Date[:\s]+([\d\/\-]+)/i', $text, $matches)) {
            $data['payment_date'] = trim($matches[1]);
        }

        // Parse Regular earnings (Hours, Rate, Amount, YTD)
        if (preg_match('/Regular.*?([\d\.]+)\s+([\d\.]+)\s+([\d,\.]+)\s+([\d,\.]+)/is', $text, $matches)) {
            $data['regular_hours'] = floatval($matches[1]);
            $data['regular_rate'] = floatval($matches[2]);
            $data['regular_current'] = floatval(str_replace(',', '', $matches[3]));
            $data['regular_ytd'] = floatval(str_replace(',', '', $matches[4]));
        }

        // Parse Stat Holiday earnings
        if (preg_match('/Stat\s*Holiday\s*[Pp]aid.*?([\d\.]+)\s+([\d\.]+)\s+([\d,\.]+)\s+([\d,\.]+)/is', $text, $matches)) {
            $data['stat_hours'] = floatval($matches[1]);
            $data['stat_rate'] = floatval($matches[2]);
            $data['stat_current'] = floatval(str_replace(',', '', $matches[3]));
            $data['stat_ytd'] = floatval(str_replace(',', '', $matches[4]));
        }

        // Parse Overtime earnings
        if (preg_match('/Overtime.*?([\d\.]+)\s+([\d\.]+)\s+([\d,\.]+)\s+([\d,\.]+)/is', $text, $matches)) {
            $data['overtime_hours'] = floatval($matches[1]);
            $data['overtime_rate'] = floatval($matches[2]);
            $data['overtime_current'] = floatval(str_replace(',', '', $matches[3]));
            $data['overtime_ytd'] = floatval(str_replace(',', '', $matches[4]));
        }

        // Calculate total hours
        $data['total_hours'] = ($data['regular_hours'] ?? 0) + ($data['stat_hours'] ?? 0) + ($data['overtime_hours'] ?? 0);

        // Parse Total/Gross earnings
        if (preg_match('/(?:Total|Gross).*?(?:[\d\.]+\s+)?(?:[\d\.]+\s+)?([\d,\.]+)\s+([\d,\.]+)/is', $text, $matches)) {
            $data['total_current'] = floatval(str_replace(',', '', $matches[1]));
            $data['total_ytd'] = floatval(str_replace(',', '', $matches[2]));
        }

        // Parse CPP Employee deduction (negative values)
        if (preg_match('/CPP\s*-?\s*Employee\s+(-?[\d,\.]+)\s+(-?[\d,\.]+)/is', $text, $matches)) {
            $data['cpp_emp_current'] = abs(floatval(str_replace(',', '', $matches[1])));
            $data['cpp_emp_ytd'] = abs(floatval(str_replace(',', '', $matches[2])));
        }

        // Parse EI Employee deduction (negative values)
        if (preg_match('/EI\s*-?\s*Employee\s+(-?[\d,\.]+)\s+(-?[\d,\.]+)/is', $text, $matches)) {
            $data['ei_emp_current'] = abs(floatval(str_replace(',', '', $matches[1])));
            $data['ei_emp_ytd'] = abs(floatval(str_replace(',', '', $matches[2])));
        }

        // Parse Federal Income Tax (negative values)
        if (preg_match('/Federal\s*Income\s*Tax\s+(-?[\d,\.]+)\s+(-?[\d,\.]+)/is', $text, $matches)) {
            $data['fit_current'] = abs(floatval(str_replace(',', '', $matches[1])));
            $data['fit_ytd'] = abs(floatval(str_replace(',', '', $matches[2])));
        }

        // Parse Total Deductions from PDF (after Federal Income Tax, before Net Pay)
        if (preg_match('/Federal\s*Income\s*Tax.*?Total\s+(-?[\d,\.]+)\s+(-?[\d,\.]+)/is', $text, $matches)) {
            $data['total_deduction_current'] = abs(floatval(str_replace(',', '', $matches[1])));
            $data['total_deduction_ytd'] = abs(floatval(str_replace(',', '', $matches[2])));
        } else {
            // Fallback: Calculate if not found in PDF
            $data['total_deduction_current'] = ($data['cpp_emp_current'] ?? 0) + ($data['ei_emp_current'] ?? 0) + ($data['fit_current'] ?? 0);
            $data['total_deduction_ytd'] = ($data['cpp_emp_ytd'] ?? 0) + ($data['ei_emp_ytd'] ?? 0) + ($data['fit_ytd'] ?? 0);
        }

        // Parse VAC Earned
        if (preg_match('/VAC\s*Earned.*?([\d,\.]+)\s+([\d,\.]+)/is', $text, $matches)) {
            $data['vac_earned_current'] = floatval(str_replace(',', '', $matches[1]));
            $data['vac_earned_ytd'] = floatval(str_replace(',', '', $matches[2]));
        }

        // Parse VAC Paid
        if (preg_match('/VAC\s*Paid.*?([\d,\.]+)\s+([\d,\.]+)/is', $text, $matches)) {
            $data['vac_paid_current'] = floatval(str_replace(',', '', $matches[1]));
            $data['vac_paid_ytd'] = floatval(str_replace(',', '', $matches[2]));
        }

        // Parse Net Pay
        if (preg_match('/Net\s*Pay.*?([\d,\.]+)/is', $text, $matches)) {
            $data['net_pay'] = floatval(str_replace(',', '', $matches[1]));
        }

        return $data;
    }

    /**
     * Extract and parse payroll data from a PDF file (all pages).
     *
     * @param string $filePath
     * @return array
     */
    public function parse($filePath)
    {
        $pageTexts = $this->extractTextByPages($filePath);
        $allEmployeesData = [];
        
        foreach ($pageTexts as $index => $pageText) {
            $parsedData = $this->parsePayrollData($pageText);
            $allEmployeesData[] = [
                'page_number' => $index + 1,
                'page_text' => $pageText,
                'parsed_data' => $parsedData,
            ];
        }
        
        return [
            'total_pages' => count($pageTexts),
            'employees' => $allEmployeesData,
        ];
    }
}
