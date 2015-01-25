

var x86 = {
	/*
		MODES
			MEM AT REGISTER 
				000 = BX+SI
				001 = BX+DI
				010 = BP+SI
				011 = BP+DI
				100 = SI
				101 = DI
				101 = BP
				111 = BX
			00
				XXX = MEM AT REGISTER
				ZERO DISPLACEMENT
			01
				XXX = MEM AT REGISTER
				SIGNED DISPLACEMENT
			10
				XXX = MEM AT REGISTER
			11
				XXX = REG
				
			
		MOV REG/MEM
			[d] = direction (1 = to reg; 0 = from reg)
			[w] = width (1 = word, 0 = byte)
			100010dw 	MM-REG-XXX
			8H  8H+??dw 
			
			MOV REG->REG/MEM
				1000100w MM-REG-XXX
			MOV REG<-REG/MEM
				1000101w MM-REG-XXX
			
			
		MOV IMM->REG/MEM
			1100011w	MM-000-XXX
			CH + 6H 
		MOV IMM->REG
		
		MOV MEM->ACC
		MOV ACC->MEM
		
		MOV REG/MEM->SREG
		MOV SREG->REG/MEM
		
	*/
};	