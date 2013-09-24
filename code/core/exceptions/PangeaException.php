<?php

class PangeaException extends Exception{
	
}

class PangeaRuntimeException extends PangeaException{

}

class PangeaClassCastException extends PangeaRuntimeException{

}

class PangeaIllegalArgumentException extends PangeaRuntimeException{

}

class PangeaIndexOutOfBoundsException extends PangeaRuntimeException{

}

class PangeaNullPointerException extends PangeaRuntimeException{

}

class PangeaDataAccessException extends PangeaException{

}

class PangeaConnectException extends PangeaDataAccessException{

}

class PangeaIOException extends PangeaException{

}