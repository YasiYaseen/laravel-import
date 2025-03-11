<?php

namespace App\Http\Controllers;

use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use App\Models\Customer;
use Exception;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $data = [];
        foreach ($reader->getSheetIterator() as $sheet) {

            foreach ($this->generateRows($sheet) as $rowData) {
                // dd($rowData);

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
            $rowData = $row->toArray();

            if ($rowCount === 0) {
                // First row → Store headers
                $headers = array_map('trim', $rowData);
                $rowCount++;
                continue;
            }
           // Ensure we only take data where headers exist
            $filteredData = [];
            foreach ($headers as $index => $header) {
                if (!empty($header)) {
                    $filteredData[$header] = $rowData[$index] ?? null;
                }
            }

            yield $filteredData;

            $rowCount++;

            // Free memory every 1000 rows
            if ($rowCount % 1000 === 0) {
                gc_collect_cycles();
            }
        }
    }
    //
    public function countRowsSpatie(Request $request)
    {
        $file = $request->file('file');
        $fileType = $this->detectFileType($file);

        if (!$fileType) {
            return response()->json(['message' => 'Unsupported file format.'], 400);
        }

        $start = microtime(true);
        $rowCount = SimpleExcelReader::create($file, $fileType)->getRows()->count();
        $executionTime = round(microtime(true) - $start, 2);

        return response()->json([
            'library' => 'Spatie Simple Excel',
            'rows' => $rowCount,
            'time' => $executionTime
        ]);
    }

    public function countRowsLaravelExcel(Request $request)
    {
        $file = $request->file('file');

        $start = microtime(true);
        $rowCount = Excel::toCollection(new CustomersImport, $file)->first()->count();
        $executionTime = round(microtime(true) - $start, 2);

        return response()->json([
            'library' => 'Laravel Excel',
            'rows' => $rowCount,
            'time' => $executionTime
        ]);
    }

    public function countRowsFastExcel(Request $request)
    {
        $file = $request->file('file');
        $fileType = $this->detectFileType($file);

        if (!$fileType) {
            return response()->json(['message' => 'Unsupported file format.'], 400);
        }

        $start = microtime(true);
        $rowCount = count((new FastExcel)->import($file->path()));
        $executionTime = round(microtime(true) - $start, 2);

        return response()->json([
            'library' => 'Fast Excel',
            'rows' => $rowCount,
            'time' => $executionTime
        ]);
    }

    public function countRowsOpenSpout(Request $request)
    {
        $file = $request->file('file');
        $fileType = $this->detectFileType($file);

        if (!$fileType) {
            return response()->json(['message' => 'Unsupported file format.'], 400);
        }

        $start = microtime(true);
        $reader = new XlsxReader();
        $reader->open($file->path());

        $rowCount = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            $rowCount = iterator_count($sheet->getRowIterator());
            break;
        }

        $reader->close();
        $executionTime = round(microtime(true) - $start, 2);

        return response()->json([
            'library' => 'OpenSpout',
            'rows' => $rowCount,
            'time' => $executionTime
        ]);
    }



}
