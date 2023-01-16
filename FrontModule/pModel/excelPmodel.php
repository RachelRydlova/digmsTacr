<?php

namespace A2bModels;

use A2bModels,
    \Nette\Caching\Cache;

/**
 * excel Pmodel - p model pro ukladani dat do excelu
 *
 * @author Ondra310
 */
class excelPmodel extends karatModel
 {
    /**
     * exportni dir v ramci www
     */
    CONST exportDir =  '/generated';
    
   
   
    /**
     *
     * @var connectLocal 
     */
    protected $local;
    
    /**
     *
     * @var A2bFunc\applCache 
     */
    protected $cache;

    //$returnCache = $this->cache->load($fullCacheKey);
    //$this->cache->save($fullCacheKey, $returnArr, array(\Nette\Caching\Cache::EXPIRE=>self::fullNomenExpire));
    /**
     * @param \A2bModels\connectLocal $local
     */
    public function __construct(connectLocal $local, \A2bFunc\applCache $cache) {
        $this->local = $local;
        $this->cache = $cache;
    }//end function construct...........................................................................................
    
    
    /**
     * ziska datetime s nastavenym casem pro invalidaci cache
     * cache invaliduje zitrejsi den rano v 6 hodin
     * @return \DateTime
     */
    protected function getCacheExpirTime(){
        $expirTime = new \DateTime('tomorrow');
        $expirTime->modify('+6 hours');
        return $expirTime;
    }//end function getCacheExpirTime...................................................................................

        /**
    * vrati user DB ID pro login, suppert functin 
    * @param type $login
    * @return int
    */
    protected function getUserID($login){
        return $this->local->database->table('user')->select('id')->where(array('login'=>$login))->fetch()->id;
    }//end function getUserID...........................................................................................
    
        
    /**
     *  
     * @param datetim $fromDate
     * @return boolean
     */
    public function getDataForWind($fromDate,$toDate){
        $sqlStr = " SELECT * FROM `weather` WHERE TS between :fromDate AND :toDate";
        $sth = $this->local->pdo->prepare($sqlStr);
        if (!$sth){
            $this->errorHandler($this->local->pdo);
            return FALSE;
        }
        
        $sth->execute(array(':fromDate' => $fromDate,':toDate'=>$toDate));
        $newData = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $newData;
                
    }//end function getNomenPrices......................................................................................
    
   /**
     * ulozi data do excelu
     * @param string $fileName
     * @param array $data
     * @param array $zahlavi
     * @param string $title
     * @param string $subject
     * @param string $desc
     * @return string|boolean
     */
    public function saveExcel($fileName, $data, $zahlavi, $title = NULL, $subject=NULL, $desc=NULL){
        $objPHPExcel = new \PHPExcel();
        
        // Set document properties
        $objPHPExcel->getProperties()->setCreator("VAPOL CZ")
                                    ->setLastModifiedBy("VAPOL CZ")
                                    ->setTitle($title)
                                    ->setSubject($subject)
                                    ->setDescription($desc)
                                    ;


       $objPHPExcel->setActiveSheetIndex(0);
       $defWidth = 11;
       $i = array('A'=>15,'B'=>$defWidth,'C'=>$defWidth,'D'=>$defWidth,'E'=>$defWidth,'F'=>$defWidth,'G'=>$defWidth,'H'=>$defWidth,'I'=>$defWidth,'J'=>$defWidth,
                  'K'=>$defWidth,'L'=>$defWidth,'M'=>$defWidth,'N'=>$defWidth,'O'=>$defWidth,'P'=>$defWidth,'Q'=>$defWidth,'R'=>$defWidth,'S'=>$defWidth,'T'=>$defWidth,
                  'U'=>$defWidth,'V'=>$defWidth,'W'=>$defWidth,'X'=>$defWidth,'Y'=>$defWidth,'Z');
       
       $styleArray = array( 'font' => array( 'bold' => TRUE ));

       $sheet = $objPHPExcel->getActiveSheet();
       reset($i);
       foreach ($zahlavi as $key=>$value){
           $index = key($i);
           $width = current($i);
           $sheet ->setCellValue($index.'1', $value);
           $sheet->getColumnDimension($index)->setWidth($width);
           $sheet->getStyle($index.'1')->applyFromArray($styleArray);
           next($i);
           }
     

       $objPHPExcel->getActiveSheet()->fromArray($data, NULL, 'A2');

       $objPHPExcel->getActiveSheet()->setTitle($title);

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
       $objPHPExcel->setActiveSheetIndex(0);

       
        // Redirect output to a clients web browser (Excel2007)
       //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
       //header('Content-Disposition: attachment;filename="01simple.xlsx"');
       //header('Cache-Control: max-age=0');

       $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
       $filePath = self::exportDir.$fileName;
       $fileName = WWW_DIR.$filePath;
       
       try {
            $objWriter->save($fileName);
       }
       catch (\Exception $e){
            $chyby = $e->getMessage();
            $this->setMessage(pritn_r($chyby,true), 'error');
            return $fileName;
            }
       return $filePath;
    }//end function saveExcel...........................................................................................

    
}//end class reportPmodel...............................................................................................
?>