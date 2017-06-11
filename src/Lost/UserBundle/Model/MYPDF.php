<?php
namespace Lost\UserBundle\Model;

use \TCPDF;

class MYPDF extends TCPDF {
       
        //Page header
        public function Header() {
            $headerData = $this->getHeaderData();
            
            $this->SetFont('helvetica', 'B', 10);
            $this->writeHTML($headerData['string']);
    
        }
    
        // Page footer
        public function Footer() {
            // Position at 25 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
    
            $this->Cell(0, 0, 'Lost Telecom Group - Lost Portal', 0, 0, 'C');
            $this->Ln();
            $this->Cell(0,0,'www.Losttelecom.com', 0, false, 'C', 0, '', 0, false, 'T', 'M');
    
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    
}
