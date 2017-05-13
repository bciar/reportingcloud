<?php
 
// Turn up error reporting
error_reporting (E_ALL|E_STRICT);

// Use db settings from PHP Generator for MySQL
require_once 'phpgen_settings.php';
require_once 'database_engine/mysql_engine.php';

require 'vendor/autoload.php';
use TxTextControl\ReportingCloud\ReportingCloud;

/**
 * Convert a PHP assoc array to a SOAP array of array of string
 *
 * @param array $assoc
 * @return array
 */
function assocArrayToArrayOfArrayOfString ($assoc)
{
    $arrayKeys   = array_keys($assoc);
    $arrayValues = array_values($assoc);
 
    return array ($arrayKeys, $arrayValues);
}
 
/**
 * Convert a PHP multi-depth assoc array to a SOAP array of array of array of string
 *
 * @param array $multi
 * @return array
 */
function multiAssocArrayToArrayOfArrayOfString ($multi)
{
    $arrayKeys   = array_keys($multi[0]);
    $arrayValues = array();
 
    foreach ($multi as $v) {
        $arrayValues[] = array_values($v);
    }
 
    $_arrayKeys = array();
    $_arrayKeys[0] = $arrayKeys;
 
    return array_merge($_arrayKeys, $arrayValues);
}

// --------------------------------------------

// Don't run if invoice_id not included in GET request
if (isset($_GET['invoice_id'])) {

// Connect to MySQL db
$connectionSettings = GetGlobalConnectionOptions();
$mysqli = new mysqli($connectionSettings["server"], $connectionSettings["username"], $connectionSettings["password"], $connectionSettings["database"]);
if ($mysqli->connect_errno) {
	printf("Connect failed: %s\n", $mysqli->connect_error);
	exit();
}

if (!$mysqli->set_charset("utf8")) {
	printf("Error loading character set utf8: %s\n", $mysqli->error);
	exit();
}

// Get requested incoice id
$invoice_id = $_GET['invoice_id'];

// Define query for invoice data
$query = "SELECT      invoice.number AS invoice_no,     invoice.date,     invoice.vat,     invoice.comment,     currency.iban,     currency.code AS currency,     currency.conversion,     invoicetype.text AS invoice_type,     address_bill.name AS bill_name,     address_bill.coname AS bill_coname,     address_bill.contactName AS bill_contactname,     address_bill.address1 AS bill_a1,     address_bill.address2 AS bill_a2,     address_bill.address3 AS bill_a3,     address_bill.tel AS bill_tel,     address_bill.vat AS bill_vat,     address_bill.eori AS bill_eori, address_bill.email AS bill_email,   address_ship.name AS ship_name,     address_ship.coname AS ship_coname,     address_ship.contactName AS ship_contactname,     address_ship.address1 AS ship_a1,     address_ship.address2 AS ship_a2,     address_ship.address3 AS ship_a3,     address_ship.tel AS ship_tel,     address_ship.vat AS ship_vat,     address_ship.eori AS ship_eori, address_ship.email AS ship_email,   country_bill.name AS bill_country,     country_ship.name AS ship_country,     originname.name AS originName,     invoicetype.export FROM     invoice         INNER JOIN     currency ON invoice.currency_id = currency.currency_id         INNER JOIN     invoicetype ON invoice.invoicetype_id = invoicetype.invoicetype_id         INNER JOIN     customer ON invoice.customer_id = customer.customer_id         INNER JOIN     address address_bill ON customer.addressBill_id = address_bill.address_id         INNER JOIN     address address_ship ON customer.addressShip_id = address_ship.address_id         INNER JOIN     country country_bill ON address_bill.country_id = country_bill.country_id         INNER JOIN     country country_ship ON address_ship.country_id = country_ship.country_id         LEFT OUTER JOIN     originname ON invoice.originname_id = originname.originname_id WHERE     invoice.invoice_id = ?";

// Get invoice data and create address fields
if ($stmt = $mysqli->prepare($query)) {
	$stmt->bind_param('i', $invoice_id); //FIXME: param types: s- string, i- integer, d- double, b- blob
    $stmt->execute();
    $stmt->bind_result($invoice_no, $date, $vat, $comment, $iban, $currency, $conversion, $invoice_type, $bill_name, $bill_coname, $bill_contactname, $bill_a1, $bill_a2, $bill_a3, $bill_tel, $bill_vat, $bill_eori, $bill_email, $ship_name, $ship_coname, $ship_contactname, $ship_a1, $ship_a2, $ship_a3, $ship_tel, $ship_vat, $ship_eori, $ship_email, $bill_country, $ship_country, $originName, $export);

    while ($stmt->fetch()) {
		$bill_name_s = "";
		if ($bill_coname <> "") $bill_name_s .= PHP_EOL . "c/o " . $bill_coname;
		if ($bill_contactname <> "") $bill_name_s .= PHP_EOL . $bill_contactname;
		if ($bill_a1 <> "") $bill_name_s .= PHP_EOL . $bill_a1;
		if ($bill_a2 <> "") $bill_name_s .= PHP_EOL . $bill_a2;
		if ($bill_a3 <> "") $bill_name_s .= PHP_EOL . $bill_a3;
		$bill_name_s .= PHP_EOL . $bill_country;
		if ($bill_tel <> "") $bill_name_s .= PHP_EOL . "Tel: " . $bill_tel;
		if ($bill_email <> "") $bill_name_s .= PHP_EOL . "Email: " . $bill_email;
		if ($bill_vat <> "") $bill_name_s .= PHP_EOL . "VAT No: " . $bill_vat;
		if ($bill_eori <> "") $bill_name_s .= PHP_EOL . "EORI No: " . $bill_eori;
		
		$ship_name_s = "";
		if ($ship_coname <> "") $ship_name_s .= PHP_EOL . "c/o " . $ship_coname;
		if ($ship_contactname <> "") $ship_name_s .= PHP_EOL . $ship_contactname;
		if ($ship_a1 <> "") $ship_name_s .= PHP_EOL . $ship_a1;
		if ($ship_a2 <> "") $ship_name_s .= PHP_EOL . $ship_a2;
		if ($ship_a3 <> "") $ship_name_s .= PHP_EOL . $ship_a3;
		$ship_name_s .= PHP_EOL . $ship_country;
		if ($ship_tel <> "") $ship_name_s .= PHP_EOL . "Tel: " . $ship_tel;
		if ($ship_email <> "") $ship_name_s .= PHP_EOL . "Email: " . $ship_email;
		if ($ship_vat <> "") $ship_name_s .= PHP_EOL . "VAT No: " . $ship_vat;
		if ($ship_eori <> "") $ship_name_s .= PHP_EOL . "EORI No: " . $ship_eori;
    }
    $stmt->close();
}

// Define query for line items
$query = "SELECT   item.quantity,   item.text,   item.unitPrice,   item.discount,   product.code,   product.name,   product.country_id AS origin_id,   ROUND(item.quantity * ROUND(item.unitPrice * (1 - item.discount / 100),2),2) AS lineTotal,   country.name AS origin,   hs.code AS hs FROM item   INNER JOIN product     ON item.product_id = product.product_id   LEFT OUTER JOIN country     ON product.country_id = country.country_id   INNER JOIN invoice     ON item.invoice_id = invoice.invoice_id   INNER JOIN customer     ON invoice.customer_id = customer.customer_id   INNER JOIN address     ON customer.addressShip_id = address.address_id   LEFT OUTER JOIN hs     ON hs.product_id = product.product_id     AND hs.country_id = address.country_id WHERE item.invoice_id = ?";


$blockFieldValues = array(); // Array of line items
$i = 1; // Count position number on invoice
$lineSum = ""; // Invoice sum before VAT

// Get line item data and populate array
if ($stmt = $mysqli->prepare($query)) {
	$stmt->bind_param('i', $invoice_id); //FIXME: param types: s- string, i- integer, d- double, b- blob
    $stmt->execute();
    $stmt->bind_result($quantity, $text, $unitPrice, $discount, $code, $name, $origin_id, $lineTotal, $origin, $hs);
    while ($stmt->fetch()) {
		if ($text <> "") $name .= PHP_EOL . $text;
		if ($discount <> 0) $name .= PHP_EOL . 'Discount: ' . $discount . ' %';
		if ($hs <> "") $name .= PHP_EOL . 'HS Code: ' . $hs;
		if ($export && ($origin_id <> 216) && ($origin_id <> "")) $name .= PHP_EOL . 'Country of origin: ' . $origin;
		if ($quantity == round($quantity)) $quantity = number_format($quantity, 0);
		$unitPrice = $unitPrice * (1 - $discount/100);
		$blockFieldValues[] = array (
			'pos' => $i++,
			'quantity' => $quantity,
			'unitPrice' => number_format($unitPrice, 2, '.', ' '),
			'code' => $code,
			'desc' => $name,
			'lineTotal' => number_format($lineTotal, 2, '.', ' ')
		);
		$lineSum = bcadd($lineSum,$lineTotal,2);
    }
    $stmt->close();
}

$mysqli->close();

// Calculate VAT and invoice total
$vatCharge = round(bcmul($lineSum,($vat/100),4),2);
$total = bcadd($lineSum,$vatCharge,2);

// Calculate export value in CHF for export invoices
if ($currency <> 'CHF') {
	$exportValue = 'Export value: CHF ' . number_format(ceil(bcmul($total,$conversion,1)), 0, '.', '') . '.';
}
else {
	$exportValue = "";
}

// Format invoice sums
$lineSum = number_format($lineSum, 2, '.', ' ');
$vatCharge = number_format($vatCharge, 2, '.', ' ');
$total = number_format($total, 2, '.', ' ');


// Populate invoice fields

$reportingCloud = new ReportingCloud([
    'username' => 'daniel.hoffmann@flarm.com',
    'password' => 'jQdF5j*rr8XM7#ZzHS69P&4G#',
]);
$mergeData = [
    0 => [
        'invoice_no' => $invoice_no,
        'invoice_type' => $invoice_type,
        'date' => $date,
		'ship_name' => $ship_name,
		"ship" => $ship_name_s,
		'currency'=>$currency,
		"bill_name"=>$bill_name,
		"bill"=> $bill_name_s,
        'items' => $blockFieldValues/*[
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
        ]*/,
        'subtotal' => $lineSum,
        'vat' => $vatCharge,
        'total' => $total,
        'iban' => $iban,
        'comment'=>$comment,
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
    'user_password'              => '123456789',    ////pdf password
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
}
?>