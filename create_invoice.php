<?php
 
// Turn up error reporting
error_reporting (E_ALL|E_STRICT);

require 'vendor/autoload.php';
use TxTextControl\ReportingCloud\ReportingCloud;
/**
 * Convert a PHP assoc array to a SOAP array of array of string
 *
 * @param array $assoc
 * @return array
 */

// --------------------------------------------

// Don't run if invoice_id not included in GET request
//if (isset($_GET['invoice_id'])) {

// Populate invoice fields
$invoice_no = 1;
$bill_name = "Kend Yeod";
$invoice_type = 'Credit Note';

// Turn off WSDL caching
$reportingCloud = new ReportingCloud([
    'username' => 'daniel.hoffmann@flarm.com',
    'password' => 'jQdF5j*rr8XM7#ZzHS69P&4G#',
]);
$mergeData = [
    0 => [
        'invoice_no' => '778723',
        'invoice_type' => $invoice_type,
        'date' => '20/1/2016',
		'ship_name' => "Kend",
		"ship" => "Yeod",
		'currency'=>'USD',
		"bill_name"=>"Kend Yeod",
		"bill"=>"Kend",
        'items' => [
            0 => [
				'pos' => '1',
                'quantity' => '2',
                'code' => 'www-222',
                'desc' => 'description1',
                'unitPrice' => '200.2',
                'lineTotal' => '3333'
            ],
            1 => [
				'pos' => '2',
                'quantity' => '3',
				'code'=>'abc123',
				'desc'=>'Item description 2',
                'unitPrice' => '5543',
                'lineTotal' => '5543',
            ],
        ],
        'subtotal' => '7673.4',
        'vat' => '537.138',
        'total' => '8210.538',
        'iban' => 'IBAN VALUE',
        'comment'=>'no comment',
    ],
];

// Configure Merge Setting and Choose the template
$mergeSettings = [
    'creation_date'              => time(),
    'last_modification_date'     => time(),
    'remove_empty_blocks'        => true,
    'remove_empty_fields'        => true,
    'remove_empty_images'        => true,
    'remove_trailing_whitespace' => true,
    'author'                     => 'Ken Yeo',
    'creator_application'        => 'The Giant Peach',
    'document_subject'           => 'The Old Green Grasshopper',
    'document_title'             => 'James and the Giant Peach',
    'user_password'              => '123456789',
];
$templateName = 'invoice.docx';

//Name file
$filename = 'OutputFile.pdf';
if ($invoice_type == "Credit Note") {
    $filename = $invoice_no . " - " . $bill_name . " - Credit Note.pdf";
} elseif ($invoice_type == "Quotation") {
    $filename = $invoice_no . " - " . $bill_name . " - Quotation.pdf";
} else {
    $filename = $invoice_no . " - " . $bill_name . " - Invoice.pdf";
}

//Merge document
$arrayOfBinaryData = $reportingCloud->mergeDocument($mergeData, 'PDF', $templateName, null, false, $mergeSettings);
foreach ($arrayOfBinaryData as $index => $binaryData) {
    $destinationFile     = sprintf($filename, $index);
    $destinationFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $destinationFile;
    file_put_contents($destinationFilename, $binaryData);
    var_dump("Merged {$templateName} was written to {$destinationFilename}");
}
//}
?>