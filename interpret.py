import re
import sys
from xml.dom import minidom


print("start")

class OpCodes:
    MOVE = "MOVE"
    CREATEFRAME = "CREATEFRAME"
    PUSHFRAME = "PUSHFRAME"
    POPFRAME = "POPFRAME"
    DEFVAR = "DEFVAR"
    CALL = "CALL"
    RETURN = "RETURN"
    PUSHS = "PUSHS"
    POPS = "POPS"
    ADD = "ADD"
    SUB = "SUB"
    MUL = "MUL"
    IDIV = "IDIV"
    LT = "LT"
    GT = "GT"
    EQ = "EQ"
    AND = "AND"
    OR = "OR"
    NOT = "NOT"
    INT2CHAR = "INT2CHAR"
    STRI2INT = "STRI2INT"
    READ = "READ"
    WRITE = "WRITE"
    CONCAT = "CONCAT"
    STRLEN = "STRLEN"
    GETCHAR = "GETCHAR"
    SETCHAR = "SETCHAR"
    TYPE = "TYPE"
    LABEL = "LABEL"


class RetCodes:
    wrongXMLformat = 31
    otherErrorInXML = 32
    seman = 52
    wrongOps = 53
    wrongVariable = 54
    wrongFrame = 55
    missingValue = 56
    wrongOpValue = 57
    wrongString = 58
    messageFrame = "Pokus o pristup k nedefinovanemu ramci (temporary frame neexistuje)"


class ReturnException(Exception):
    def __init__(self, message, retCode):
        super(ReturnException, self).__init__(message)
        self.retCode = retCode


class Frame:
    """Reprezentuje frame, obsahuje promenne a metody pro pridani a ziskani promennych"""
    def __init__(self):
        self.variables = []

    def AddVar(self, var):
        """Pokud promenna daneho nazvu ve frame jeste neexistuje, prida ji tam, jinak vyhodi exception"""
        if(self.FindVarByName(var.name) is None):
            self.variables.append(var)
        else:
            raise ReturnException("Redefinice promenne!", RetCodes.wrongVariable)

    def FindVarByName(self, name):
        """Podle jmena vyhleda promennou v danem frame a vrati ji, pokud neexistuje, vrati None"""
        for var in self.variables:
            if var.name == name:
                return var
        return None


class Frames:
    """Obsahuje globální a dočasný rámec a zásobník lokálních rámců (k nejnovějšímu lze přistupovat přes lf"""
    tf: Frame
    lf: Frame

    def __init__(self):
        self.gf = Frame()
        self.lf = None
        self.localFramesStack = []
        self.tf = None

    def PushFrame(self):
        if self.tf is None:
            raise ReturnException(RetCodes.messageFrame, RetCodes.wrongFrame)
        self.localFramesStack.append(self.tf)
        self.lf = self.tf
        self.tf = None

    def CreateFrame(self):
        self.tf = Frame()

    def PopFrame(self):
        if self.lf is None:
            raise ReturnException(RetCodes.messageFrame, RetCodes.wrongFrame)
        self.tf = self.localFramesStack.pop()
        if len(self.localFramesStack) == 0:
            self.lf = None
        else:
            self.lf = self.localFramesStack[-1]


class Variable:
    """Reprezentuje promennou. Obsahuje nazev, typ a hodnotu promenne"""
    def __init__(self, name, value):
        self.name = name
        self.type = None
        self.value = value


class Instruction:
    def __init__(self, opCode):
        self.opCodeType = opCode
        self.args = []

    def AddArg(self, arg):
        self.args.append(arg)


class Argument:
    def __init__(self, typeOfArg, value):
        self.type = typeOfArg
        if typeOfArg == "var":
            frameAndName = self.getFrameAndName(value)
            self.frame = frameAndName[0] #todo poresit pripad None
            self.name = frameAndName[1]
        #elif

    def getFrameAndName(self, value):
        """
        :param value: string s ramcem a nazvem promenne
        :return: tuple frame a nazev promenne
        """
        regex = re.search(r'''^([LTG]F)@([\_\-$&%*!?a-zA-Z][\_\-$&%*!?a-zA-Z0-9]*)$''', value)
        if regex != None:
            return (regex.group(1), regex.group(2))
        else:
            return None


class Program:
    def __init__(self):
        self.instructions = []

    def AddInstruction(self, instruction):
        self.instructions.append(instruction)


class XMLReader:
    def __init__(self, file):
        self.xml = minidom.parse(file)

    def _GetInstructionByOrder(self, instructions, order):
        for instr in instructions:
            if instr.attributes["order"].value == order:
                return instr
        raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)

    def GetProgram(self):
        instructions = self.xml.getElementsByTagName("instruction")
        program = Program()
        for i in range(1,len(instructions)+1):
            insXML = self._GetInstructionByOrder(instructions, i)
            program.AddInstruction(Instruction(insXML.attributes["opcode"].value))






#test
if False:
    var = Variable("x", 5)
    frames = Frames()
    frames.CreateFrame()
    frames.tf.variables.append(var)
    try:
        frames.PushFrame()
    except ReturnException as e:
        print(e.retCode)
    frames.lf.variables.append(Variable("y", 99))
    print(frames.localFramesStack[len(frames.localFramesStack)-1].variables[0].name)
    var.name = "zmenaX"
    print(frames.localFramesStack[len(frames.localFramesStack)-1].variables[0].name)
    print(frames.localFramesStack[len(frames.localFramesStack)-1].variables[1].name)

if True:
    XMLReader("xml.src")