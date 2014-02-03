<?

//================================ FUNCTIONS
//������� ����������� �������� ��� �������
function print_otlad($elem) {
	echo "<pre>";
	print_r($elem);
	echo "</pre>";
}

// ���������� ������ ����� ������� mas ��������� str
function fill_blank_array($mas, $str) {
	foreach ($mas as $key => $value) {
		if (is_blank($value)) {
			$mas[$key] = $str;
		}
	}
	return $mas;
}

//=============================== CLASS_SHIFR
class shifratorDeshifrator {
	private $method; //���������� ����� ����������
	public $source; //�������� �����
	public $encrypted; //������������� �����
		
	function __construct() {
		if (!$this->prepare()) 
			return false;
		if (method_exists("shifratorDeshifrator", $this->method)) {
			$method = $this->method;
			$this->$method();
			
			$file = fopen(ENCTEXT,"wb");
			fwrite($file, implode($this->encrypted));
			fclose($file);
			?>
			<h4 class='green' style="background-color:EFE">���������� ������ �������<br>
			<a href="<?=ENCTEXT?>" target="_blank">������� ���� � ������������� �������</a></h4><br>
			<?
		}
		else {
			echo "<p class='red'>������������ ������� ������</p>";
		}
	}
	
	// ����������
	private function prepare() {		
		$this->key = $this->prepareKey($_POST['key']);
		$this->method = $_POST['method'];
		
		$str = $_SERVER['SCRIPT_FILENAME'];
		$str = str_replace("lab2.php", "", $str);
		
		define("PLAINTEXT", "text/".$_POST['text']);
		define("ENCTEXT", "text/".$_POST['text'].".enc");
		$this->source = trim($_POST['text']);
		if (strlen($this->source) == 0)
			$this->source = "plaintext";
		if (file_exists(PLAINTEXT)) {
			$this->source = '';
			copy($str.PLAINTEXT,$str.ENCTEXT);
			$file = fopen(PLAINTEXT, "rb");
			while (!feof($file)) {
				$buf = fread($file, 1);
				$this->source .= $buf;
			}
			fclose($file);
			return true;
		}
		else echo "<p class='red'>���� �� ����� ���� ������</p>";
		return false;
	}
	
	// ���������� ����� � ������� ����
	private function prepareKey($mes) {
		$sum = 0;
		for ($i=0; $i<strlen($mes); $i++) {
			$sum += ord($mes[$i]);
		}
		return $sum;
	}
	
	// ����� RFSR
	private function method_rfsr() {
		$mainMask = 0x3FFFFFFF; //2^30-1
		// ��������� �������� ����� I
		$key = $this->key & $mainMask; 
		// �������� � �������, ���������� �� 8, �� ���� 32
		$key = $this->lfsr($this->lfsr($this->lfsr($key)));
		
		for ($i=0; $i<strlen($this->source); $i++) {
			$curLetter = ord($this->source[$i]);
			
			$curKey = ($key & 0xFF000000) >> 24;
			
			// ���������� ��� 8 ��� �����
			for ($j=0; $j<8; $j++) {
				$key = $this->lfsr($key);
			}
			$key &= 0xFFFFFFFF;
			$encrLetter = $curLetter ^ $curKey;
			$this->encrypted[$i] = chr($encrLetter);
		}
	}
	
	// ����� �����
	private function method_geffe() {
		$mainMask = 0x1FFFFFFFFF; //2^38-1
		// ��������� �������� ����� I
		$key = $this->key & $mainMask; 
		// �������� � �������, ���������� �� 8, �� ���� 40
		$key = $this->geffe($this->geffe($this->geffe($key)));
		
		for ($i=0; $i<strlen($this->source); $i++) {
			$curLetter = ord($this->source[$i]);
			
			$curKey = ($key & 0xFF000000) >> 24;
			
			// ���������� ��� 8 ��� �����
			for ($j=0; $j<8; $j++) {
				$key = $this->geffe($key);
			}
			$key &= 0xFFFFFFFF;
			$encrLetter = $curLetter ^ $curKey;
			$this->encrypted[$i] = chr($encrLetter);
		}
	}
	
	// ����� rc4
	private function method_rc4() {
		$key = $_POST['key'];
		
		// ���������� ������� S
		$s = array();
		for ($i=0; $i<256; $i++)
			$s[$i] = $i;
			
		$j=0;
		for ($i=0; $i<256; $i++) {
			$j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
			$temp = $s[$i];
			$s[$i] = $s[$j];
			$s[$j] = $temp;
		}
		
		for ($i=0, $a = $b = 0; $i<strlen($this->source); $i++) {
			$curLetter = ord($this->source[$i]);
			
			$a = ($a + 1) % 256;
			$b = ($b + $s[$a]) % 256;
			$temp = $s[$a];
			$s[$a] = $s[$b];
			$s[$b] = $temp;
			$curKey = $s[($s[$a] + $s[$b]) % 256];
			
			$encrLetter = $curLetter ^ $curKey;
			$this->encrypted[$i] = chr($encrLetter);
		}
	}
	
	// ���� LFSR
	private function lfsr($key) {
		$bit = (($key & (1<<1))>>1) ^ (($key & (1<<28))>>28);
		$key <<= 1;
		$key |= $bit;
		return $key;
	}
	
	// ���� �����
	private function geffe($key) {
		$x1 = $this->lfsr1($key);
		$x2 = $this->lfsr2($key);
		$x3 = $this->lfsr3($key);
		$bit = ($x1 & $x2) | ((~$x1) & $x3);
		$key <<= 1;
		$key |= $bit;
		return $key;
	}
	
	// ���� LFSR1
	private function lfsr1($key) {
		$bit = (($key & (1<<1))>>1) ^ (($key & (1<<28))>>28);
		return $bit;
	}
	// ���� LFSR2
	private function lfsr2($key) {
		$bit = (($key & (1<<1))>>1) ^ (($key & (1<<9))>>9) ^ (($key & (1<<11))>>11) ^ (($key & (1<<36))>>36);
		return $bit;
	}
	// ���� LFSR3
	private function lfsr3($key) {
		$bit = (($key & (1<<0))>>0) ^ (($key & (1<<6))>>6) ^ (($key & (1<<7))>>7) ^ (($key & (1<<26))>>26);
		return $bit;
	}
}


// ======================================= PAGE

?>
<head>
	<title>�� �2</title>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
	<link rel="stylesheet" href="style.css" />
	<script src="jquery.min.js"></script>
	<script src="script-2.js"></script>
</head>
<body id="top">
	<h1>������������ ������ �2 �� ��</h1>
	<a href="#shifr" id="set-shifr"><h2>���������� ������</h2></a>
	<div id="shifr">
		<div class="accept">
			<?
				if (isset($_POST['accept'])) {
					$shifr = new shifratorDeshifrator;
				}
			?>
			<form action="" id="form-shifr" method="post">
				<strong>�������� ����� ����������:</strong>
				<select name="method" id="method">
					<option value="method_rfsr" selected>1. LFSR</option>
					<option value="method_geffe">2. �������� �����</option>
					<option value="method_rc4">3. �������� RC4</option>
				</select><br>
				<p><strong id="label-key">������� ���� ����������: </strong><input type="text" name="key" id="key" value="key" min="1" required></p>
				<p><strong>������� ��� ����� ��� ���������� (��� �������� ������ ��� ���������� ����� plaintext):</strong>
				<input name="text" id="text" type="text" value="plaintext"></p>
				<input type="hidden" name="type" value="shifr">
				<input type="submit" name="accept" value="�����������" class="button">
			</form>
		</div>
	</div>
	<br>
	
	<h1>����������� ���������: ������� ������ 051004 ����� ������</h1>
</body>