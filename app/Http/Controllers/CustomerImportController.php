<?php

namespace App\Http\Controllers;

use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use App\Models\Customer;
use Generator;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use Rap2hpoutre\FastExcel\FastExcel;
use Maatwebsite\Excel\Facades\Excel;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ODS\Reader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Reader\XLSX\Sheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class CustomerImportController extends Controller
{
    public function showForm()
    {
        return view('import');
    }

    private function detectFileType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, ['csv', 'xlsx']) ? $extension : null;
    }

    public function importSpatie(Request $request)
    {
        $file = $request->file('file');
        $fileType = $this->detectFileType($file);

        if (!$fileType) {
            return response()->json(['message' => 'Unsupported file format. Only CSV and XLSX are allowed.'], 400);
        }

        $reader = SimpleExcelReader::create($file, $fileType);
        $reader->getRows()->each(function($row){

            // Customer::create([
            //     'first_name' => $row['first_name'] ?? null,
            //     'last_name'  => $row['last_name'] ?? null,
            //     'email'      => $row['email'] ?? null,
            //     'gender'     => $row['gender'] ?? null,
            //     'phone'      => $row['phone'] ?? null,
            //     'country'    => $row['country'] ?? null,
            //     'city'       => $row['city'] ?? null,
            // ]);
        });

        return response()->json(['message' => 'Import successful']);
    }

    public function importLaravelExcel(Request $request)
    {
        Excel::import(new CustomersImport, $request->file('file'));
        return response()->json(['message' => 'Import successful']);
    }

    public function importFastExcel(Request $request)
    {
        $file = $request->file('file');
        $fileType = $this->detectFileType($file);

        if (!$fileType) {
            return response()->json(['message' => 'Unsupported file format. Only CSV and XLSX are allowed.'], 400);
        }

        $customers = (new FastExcel)->import($file->path());

        foreach ($customers as $row) {
            // Customer::create([
            //     'first_name'  => $row['first_name'] ?? null,
            //     'last_name'  => $row['last_name'] ?? null,
            //     'email' => $row['email'] ?? null,
            //     'gender	' => $row['gender'] ?? null,
            //     'phone' => $row['phone'] ?? null,
            //     'country' => $row['country'] ?? null,
            //     'city' => $row['city'] ?? null,
            // ]);
        }
        return response()->json(['message' => 'Import successful']);
    }

    public function importOpenSpout(Request $request)
    {
        $file = $request->file('file');
        $fileType = $this->detectFileType($file);

        if (!$fileType) {
            return response()->json(['message' => 'Unsupported file format. Only CSV and XLSX are allowed.'], 400);
        }

        $reader = new XlsxReader();
        $reader->open($file->path());
        dd(get_class_methods($reader));
        $data = [];
        foreach ($reader->getSheetIterator() as $sheet) {

            foreach ($this->generateRows($sheet) as $rowData) {
                $customer = Customer::create([
                    'first_name' => $rowData[1] ?? null,
                    'last_name'  => $rowData[2] ?? null,
                    'email'      => $rowData[3] ?? null,
                    'gender'     => $rowData[4] ?? null,
                    'phone'      => $rowData[5] ?? null,
                    'country'    => $rowData[6] ?? null,
                    'city'       => $rowData[7] ?? null,
                ]);

                unset($customer); // Remove reference to free memory

            }
        }

        $reader->close();
        gc_collect_cycles(); // Free memory manually

        return response()->json(['message' => 'Import successful', 'usage' => memory_get_usage(), 'peak' => memory_get_peak_usage()]);
    }

    /**
     * Generator function to yield rows one by one (memory-efficient)
     */
    private function generateRows(Sheet $sheet)
    {
        $rowCount = 0;
        foreach ($sheet->getRowIterator() as $row) {

            if (++$rowCount == 1) {
                continue;
            }
            yield $row->toArray();

            if ($rowCount % 1000 === 0) {
                gc_collect_cycles();
            }

        }
    }


}
