<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Session;
use think\Loader;

class SystemOptionLog extends Model
{
    const NUM = 10;
    protected $logCode = 'c_app_system_option_code';
    protected $optionLog = 'c_app_system_option_log';
    protected $manager = 'c_app_manager';
    //查询操作码
    public function getLogCode($option){
        try{
            $field = 'opt_name';
            $re = $this->table($this->logCode)->field($field)->where('id',$option)->find();
            return $re;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4578,'获取操作失败');
    }

    //操作日志入库
    public function insertLog($data){
        try{
            $re = $this->table($this->optionLog)->insert($data);
            return $re;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4579,'操作入库失败');
    }

    //日志列表
    public function getLogLists($page,$sTimeKey,$eTimeKey,$userKey,$searchKey){
        try{
            $n = self::NUM;
            $m = ($page-1)*$n;

            $sTimeCon = '';
            if(!empty($sTimeKey)){
                $sTimeKey = strtotime($sTimeKey);
                $sTimeCon = " AND `l`.`create_at`>".$sTimeKey;
            }

            $eTimeCon = '';
            if(!empty($eTimeKey)){
                $eTimeKey = strtotime($eTimeKey);
                $eTimeCon = " AND `l`.`create_at`<".$eTimeKey;
            }

            $userCon = '';
            if (!empty($userKey)){
                $userCon = " AND `m`.`username` LIKE '".$userKey."%'";
            }

            $searchCon = '';
            if (!empty($searchKey)){
                $searchCon = " AND `l`.`content` LIKE '%".$searchKey."%'";
            }

            $field = 'l.id,l.create_at,l.uid,l.ip,l.content,m.username';
            $sql = "SELECT {$field} 
                FROM {$this->optionLog} `l` 
                LEFT JOIN {$this->manager} `m` 
                ON `l`.`uid`=`m`.`id` 
                WHERE 1".$sTimeCon.$eTimeCon.$userCon.$searchCon."
                ORDER BY `l`.`create_at` DESC 
                LIMIT {$m},{$n}";
            $data = $this->query($sql);

            $countSql = "SELECT count(*) AS sum
                FROM {$this->optionLog} `l` 
                LEFT JOIN {$this->manager} `m` 
                ON `l`.`uid`=`m`.`id` 
                WHERE 1".$sTimeCon.$eTimeCon.$userCon.$searchCon;
            $count = $this->query($countSql)[0]['sum'];
            Session::set('page_sum',$count);
            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4580,'查询日志列表失败');
    }

    public function exportLogReport(){
        try{
            Loader::import('Classes.PHPExcel', EXTEND_PATH);
            import('Classes.PHPExcel', EXTEND_PATH);
            Loader::import('Classes.PHPExcel.Writer.Excel5', EXTEND_PATH);
            import('Classes.PHPExcel', EXTEND_PATH);

            $objPHPExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
            $sheets=$objPHPExcel->getActiveSheet()->setTitle('操作日志');

            $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', 'ID')->setCellValue('B1', '时间')->setCellValue('C1', '账号')->setCellValue('D1', '登录IP')->setCellValue('E1', '操作记录');

            $field = 'l.id,l.create_at,l.uid,l.ip,l.content,m.username';
            $sql = "SELECT {$field} 
                FROM {$this->optionLog} `l` 
                LEFT JOIN {$this->manager} `m` 
                ON `l`.`uid`=`m`.`id` 
                ORDER BY `l`.`create_at` DESC LIMIT 500";
            $lists = $this->query($sql);
            $i=2;
            foreach($lists as $v){
                $sheets=$objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$v['id']);
                $sheets=$objPHPExcel->getActiveSheet()->setCellValue('B'.$i,date("Y-m-d H:i:s",$v['create_at']));
                $sheets=$objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$v['username']);
                $sheets=$objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$v['ip']);
                $sheets=$objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$v['content']);
                $i++;
            }

            $objPHPExcel->getDefaultStyle()->getFont()->setName( '宋体');//字体
            $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);//字体大小

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20); 
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(80);

            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true); 
            $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true); 
            $objPHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setBold(true); 
            $objPHPExcel->getActiveSheet()->getStyle('D1')->getFont()->setBold(true); 
            $objPHPExcel->getActiveSheet()->getStyle('E1')->getFont()->setBold(true); 

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="操作日志.xls"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $objWriter->save('php://output');
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4580,'操作失败');
    }
}
