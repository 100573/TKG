<?php

/**
 * 共通関数
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * 台車No・箱No採番
 */
function assignDaishaAndBoxNo($parts, $rules, $isMatome)
{
    if ($isMatome) {
        return assignMatomeNumbers($parts, $rules);
    } else {
        return assignTounyuNumbers($parts, $rules);
    }
}

/**
 * 投入順部品の台車・箱No採番
 */
function assignTounyuNumbers($parts, $rules)
{
    $result = [];
    $daishaNo = 1;
    $boxNo = 1;
    $currentDaishaLoad = [];

    // 基準部品特定
    $kijunPart = null;
    foreach ($rules as $rule) {
        if (isset($rule['type']) && strpos($rule['type'], '基準') !== false) {
            $kijunPart = $rule;
            break;
        }
    }

    if (!$kijunPart) {
        // 基準部品がない場合は最初のルールを使用
        $kijunPart = $rules[0] ?? null;
    }

    // 投入順にソート（数値比較、同一seqは order_index で安定ソート）
    usort($parts, function ($a, $b) {
        $aseq = isset($a['seq']) ? intval($a['seq']) : 0;
        $bseq = isset($b['seq']) ? intval($b['seq']) : 0;

        if ($aseq === $bseq) {
            $aidx = isset($a['order_index']) ? intval($a['order_index']) : 0;
            $bidx = isset($b['order_index']) ? intval($b['order_index']) : 0;
            return $aidx - $bidx;
        }

        return $aseq - $bseq;
    });

    foreach ($parts as $part) {
        // ルー���検索
        $rule = findRule($rules, $part['part_number']);

        if (!$rule) {
            continue;
        }

        $boxQty = $rule['box_qty'] ?? 1;
        $daishaQty = $rule['daisha_qty'] ?? 1;
        $partQty = $part['qty'];

        // 必要箱数計算
        $requiredBoxes = ceil($partQty / $boxQty);

        for ($i = 0; $i < $requiredBoxes; $i++) {
            $qtyInBox = min($boxQty, $partQty - ($i * $boxQty));

            // 台車の最大積載確認
            if (!isset($currentDaishaLoad[$part['part_number']])) {
                $currentDaishaLoad[$part['part_number']] = 0;
            }

            if ($currentDaishaLoad[$part['part_number']] >= $daishaQty) {
                // 次の台車へ
                $daishaNo++;
                $currentDaishaLoad = [];
                $currentDaishaLoad[$part['part_number']] = 0;
            }

            $result[] = array_merge($part, [
                'daisha_no' => $daishaNo,
                'box_no' => $boxNo,
                'box_qty' => $qtyInBox,
                'rule' => $rule
            ]);

            $currentDaishaLoad[$part['part_number']]++;
            $boxNo++;
        }
    }

    return $result;
}

/**
 * まとめ部品の台車・箱No採番
 */
function assignMatomeNumbers($parts, $rules)
{
    $result = [];
    $boxNo = 1;

    foreach ($parts as $part) {
        $rule = findRule($rules, $part['part_number']);

        if (!$rule) {
            continue;
        }

        $boxQty = $rule['box_qty'] ?? 1;
        $partQty = $part['qty'];

        $result[] = array_merge($part, [
            'daisha_no' => 1, // まとめは基本1台
            'box_no' => $boxNo,
            'box_qty' => $partQty,
            'rule' => $rule
        ]);

        $boxNo++;
    }

    return $result;
}

/**
 * ルール検索
 */
function findRule($rules, $partNumber)
{
    foreach ($rules as $rule) {
        if ($rule['part_number'] == $partNumber) {
            return $rule;
        }
    }
    return null;
}

/**
 * かんばん生成（Excel）
 */
function generateKanban($parts, $filePath, $line, $block, $type, $date)
{
    $spreadsheet = new Spreadsheet();

    // 台車毎にシート作成
    $daishaGroups = [];
    foreach ($parts as $part) {
        $daishaNo = $part['daisha_no'];
        if (!isset($daishaGroups[$daishaNo])) {
            $daishaGroups[$daishaNo] = [];
        }
        $daishaGroups[$daishaNo][] = $part;
    }

    $sheetIndex = 0;
    foreach ($daishaGroups as $daishaNo => $daishaParts) {
        if ($sheetIndex > 0) {
            $spreadsheet->createSheet();
        }

        $sheet = $spreadsheet->setActiveSheetIndex($sheetIndex);
        $sheet->setTitle("台車{$daishaNo}");

        // ヘッダー
        $sheet->setCellValue('A1', "{$line}_{$block}_{$type}_台車No.{$daishaNo}");
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // カラムヘッダー
        $sheet->setCellValue('A3', '機種');
        $sheet->setCellValue('B3', 'ロケーション');
        $sheet->setCellValue('C3', '部品番号');
        $sheet->setCellValue('D3', '部品名称');
        $sheet->setCellValue('E3', '箱No.');
        $sheet->setCellValue('F3', 'Qty');
        $sheet->setCellValue('G3', 'CHK');

        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // データ行
        $row = 4;
        foreach ($daishaParts as $part) {
            $sheet->setCellValue("A{$row}", $part['series']);
            $sheet->setCellValue("B{$row}", $part['location']);
            $sheet->setCellValue("C{$row}", $part['part_number']);
            $sheet->setCellValue("D{$row}", $part['part_name']);
            $sheet->setCellValue("E{$row}", $part['box_no']);
            $sheet->setCellValue("F{$row}", $part['box_qty']);
            $sheet->setCellValue("G{$row}", '');
            $row++;
        }

        // 罫線
        $lastRow = $row - 1;
        $sheet->getStyle("A3:G{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // 列幅調整
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);

        $sheetIndex++;
    }

    // 保存
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
}

/**
 * タグHTML生成
 */
function generateTagHtml($part, $type, $line, $date)
{
    $html = '<div class="tag">';
    $html .= "<div class='tag-title'>{$line}_{$part['ctg']}_台車{$part['daisha_no']}</div>";
    $html .= "<div class='tag-row'><span>投入日</span><span>{$date}</span></div>";
    $html .= "<div class='tag-row'><span>Line</span><span>{$line}</span></div>";
    $html .= "<div class='tag-row'><span>カテゴリー</span><span>{$part['ctg']}</span></div>";
    $html .= "<div class='tag-row'><span>箱No.</span><span>{$part['box_no']}</span></div>";

    $html .= '<table class="tag-table">';
    $html .= '<tr><th>機種</th><th>ロケーション</th><th>部品番号</th><th>部品名称</th><th>Qty</th><th>CHK</th></tr>';
    $html .= "<tr>";
    $html .= "<td>{$part['series']}</td>";
    $html .= "<td>{$part['location']}</td>";
    $html .= "<td>{$part['part_number']}</td>";
    $html .= "<td>{$part['part_name']}</td>";
    $html .= "<td>{$part['box_qty']}</td>";
    $html .= "<td></td>";
    $html .= "</tr>";
    $html .= '</table>';
    $html .= '</div>';
    $html .= '<div class="tag-break"></div>';

    return $html;
}
