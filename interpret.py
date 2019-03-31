import re
import sys
import getopt
import xml.etree.ElementTree as ET


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
    JUMP = "JUMP"
    JUMPIFEQ = "JUMPIFEQ"
    JUMPIFNEQ = "JUMPIFNEQ"
    EXIT = "EXIT"
    DPRINT = "DPRINT"
    BREAK = "BREAK"
    all = [MOVE, CREATEFRAME, PUSHFRAME, POPFRAME, DEFVAR, CALL, RETURN, PUSHS, POPS, ADD, SUB, MUL, IDIV, LT, GT, EQ, AND, OR, NOT, INT2CHAR, STRI2INT, READ, WRITE, CONCAT, STRLEN, GETCHAR, SETCHAR, TYPE, LABEL, JUMP, JUMPIFEQ, JUMPIFNEQ, EXIT, DPRINT, BREAK]


class RetCodes:
    success = 0
    wrongArgs = 10
    FileOpeningError = 11
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
    messageWrongOps = "Pokus o pristup k nedefinovanemu ramci (temporary frame neexistuje)"


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

    def FindVar(self, name, frame):
        if(frame.upper() == "LF"):
            if self.lf is None:
                raise ReturnException(RetCodes.messageFrame, RetCodes.wrongFrame)
            return self.lf.FindVarByName(name)
        elif(frame.upper() == "TF"):
            if self.tf is None:
                raise ReturnException(RetCodes.messageFrame, RetCodes.wrongFrame)
            return self.tf.FindVarByName(name)
        else:
            return self.gf.FindVarByName(name)

    def AddVar(self, var, frame):
        if (frame.upper() == "LF"):
            if self.lf is None:
                raise ReturnException(RetCodes.messageFrame, RetCodes.wrongFrame)
            self.lf.AddVar(var)
        elif (frame.upper() == "TF"):
            if self.tf is None:
                raise ReturnException(RetCodes.messageFrame, RetCodes.wrongFrame)
            self.tf.AddVar(var)
        else:
            self.gf.AddVar(var)


class Variable:
    """Reprezentuje promennou. Obsahuje nazev, typ a hodnotu promenne"""
    def __init__(self, name):
        self.name = name
        self.type = None
        self.value = None

    def GetValue(self):
        if self.value is None:
            raise ReturnException("Pristup k neinicializovane promenne", RetCodes.missingValue)
        return self.value

    def GetType(self):
        if self.type is None:
            raise ReturnException("Pristup k neinicializovane promenne", RetCodes.missingValue)
        return self.type


class Instruction:
    def __init__(self, opCode):
        opCode = opCode.upper()
        if opCode not in OpCodes.all:
            raise ReturnException("Neexistujici operacni kod!", RetCodes.otherErrorInXML)
        self.opCodeType = opCode
        self.args = []

    def AddArg(self, arg):
        self.args.append(arg)


class Argument:
    def __init__(self, typeOfArg, value):
        self.type = typeOfArg
        self.value = value
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

    def GetLabel(self, name):
        for instruction in self.instructions:
            if instruction.opCodeType == OpCodes.LABEL:
                if instruction.args[0].value == name:
                    return instruction
        raise ReturnException("Neexistujici label!", RetCodes.seman) #todo predelat na statickou semantickou kontrolu


class XMLReader:
    def __init__(self, file):
        self.tree = ET.parse(file)

    def GetProgram(self):
        root = self.tree.getroot()
        if root.tag != "program":
            raise ReturnException("Spatny nazev korenoveho uzlu", RetCodes.otherErrorInXML)

        program = Program()
        for i in range (1, len(root)+1):
            instrXML = self._GetNodeByOrder(root, i)
            instr = Instruction(instrXML.attrib["opcode"])
            for j in range(1, len(instrXML)+1):
                arg = self._GetNodeByTag(instrXML, "arg"+str(j))
                instr.AddArg(Argument(arg.attrib["type"], arg.text))
            program.AddInstruction(instr)
        return program

    def _GetNodeByTag(self, parent, tagName):
        try:
            for child in parent:
                if child.tag == tagName:
                    return child
        except:
            raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)
        raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)

    def _GetNodeByOrder(self, parent, order):
        try:
            for instr in parent:
                if instr.attrib["order"] == str(order):
                    return instr
        except:
            raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)
        raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)


class Stack:
    def __init__(self):
        self.value = []
        self.type = []

    def Push(self, value, type):
        self.value.append(value)
        self.type.append(type)

    def Pop(self):
        if len(self.type) > 0:
            return (self.value.pop(), self.type.pop())

"""
class XMLReader:
    def __init__(self, file):
        self.xml = minidom.parse(file)

    def _GetNodeByOrder(self, instructions, order):
        try:
            for instr in instructions:
                if instr.attributes["order"].value == str(order):
                    return instr
        except:
            raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)
        raise ReturnException("Spatny format XML", RetCodes.otherErrorInXML)

    def GetProgram(self):
        instructions = self.xml.getElementsByTagName("instruction")
        program = Program()
        for i in range(1, len(instructions)+1):
            instrXML = self._GetNodeByOrder(instructions, i)
            instr = Instruction(instrXML.attributes["opcode"].value)
            for j in range(1, 4):
                print(sys.stderr, len(instrXML.childNodes))
                try:
                    child = instrXML.getElementsByTagName("arg"+str(j))[0]
                    instr.AddArg(Argument(child.attributes["type"].value, instrXML.childNodes[0].data))
                except IndexError as e:
                    pass
            program.AddInstruction(instr)
        return program
"""


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

class Converter:
    @staticmethod
    def GetValueAndType(argument):
        if instruction.args[0].type == "var":
            var = frames.FindVar(argument.name, argument.frame)
            return (var.GetValue(), var.GetType())
        else:
            return (argument.value, argument.type)

class CheckArgs:
    def __init__(self):
        self.sourceArg = None
        self.inputArg = None

    def Check(self):
        try:
            opts, args = getopt.getopt(sys.argv[1:], "", ["help", "source=", "input="])
        except getopt.GetoptError:
            exit(RetCodes.wrongArgs)

        for opt, arg in opts:
            if opt == "--help":
                if len(opts) != 1:
                    exit(RetCodes.wrongArgs)
                print("Program načte XML reprezentaci programu ze zadaného souboru a tento program s využitím standardního vstupu a výstupu interpretuje.")
                exit(RetCodes.success)
            elif opt == "--source":
                self.sourceArg = arg
            elif opt == "--input":
                self.inputArg = arg
            else:
                exit(RetCodes.wrongArgs)

        if self.sourceArg is None and self.inputArg is None:
            exit(RetCodes.wrongArgs)
        elif self.sourceArg is None:
            self.sourceArg = sys.stdin
        elif self.inputArg is None:
            self.inputArg = sys.stdin


checker = CheckArgs()
checker.Check()
try:
    program = XMLReader(checker.sourceArg).GetProgram()
except ReturnException as e:
    exit(e.retCode)

stack = Stack()
callStack = [] #todo udelat pro to nejakou tridu osetrujici prazdny pop...
frames = Frames()

instructionCnt = 0
while instructionCnt < len(program.instructions):
    instruction = program.instructions[instructionCnt]
    sys.stdout.flush() #todo smazat
    if instruction.opCodeType == OpCodes.PUSHS:
        stack.Push(instruction.args[0].value, instruction.args[0].type)

    elif instruction.opCodeType == OpCodes.POPS:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        if var is None:
            exit(RetCodes.wrongVariable)
        var.value, var.type = stack.Pop()

    elif instruction.opCodeType == OpCodes.DEFVAR:
        frames.AddVar(Variable(instruction.args[0].name), instruction.args[0].frame)

    elif instruction.opCodeType == OpCodes.WRITE:
        def printSymbol(tupleValueType):
            if tupleValueType[1] == "int":
                print(int(tupleValueType[0]), end='')
            else:
                print(str(tupleValueType[0]), end='')
        printSymbol(Converter.GetValueAndType(instruction.args[0]))

    elif instruction.opCodeType == OpCodes.INT2CHAR:
        valueAndType = Converter.GetValueAndType(instruction.args[1])
        if(valueAndType[1] != "int"):
            exit(RetCodes.wrongOps)
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.type = "string"
        var.value = chr(int(valueAndType[0]))  #todo dodelat chybu 58

    elif instruction.opCodeType == OpCodes.MOVE:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.value, var.type = Converter.GetValueAndType(instruction.args[1])

    elif instruction.opCodeType == OpCodes.CREATEFRAME:
        frames.CreateFrame()

    elif instruction.opCodeType == OpCodes.PUSHFRAME:
        frames.PushFrame()

    elif instruction.opCodeType == OpCodes.POPFRAME:
        frames.PopFrame()

    elif instruction.opCodeType == OpCodes.CALL: #todo poresit nejakou pripravu ramce or not?
        callStack.append(instructionCnt)
        instructionCnt = program.instructions.index(program.GetLabel(instruction.args[0].value))

    elif instruction.opCodeType == OpCodes.RETURN: #todo dodealt nejaky uklid LF ot not?
        instructionCnt = callStack.pop()

    elif instruction.opCodeType == OpCodes.ADD:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.value, var.type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if not (var.GetType() == "int" and sym2Type == "int"): #aspon 1 operand neni int:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.value = int(sym2Value) + int(var.GetValue())

    elif instruction.opCodeType == OpCodes.SUB:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.value, var.type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if not (var.GetType() == "int" and sym2Type == "int"): #aspon 1 operand neni int:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.value = int(var.GetValue()) - int(sym2Value)

    elif instruction.opCodeType == OpCodes.MUL:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.value, var.type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if not (var.GetType() == "int" and sym2Type == "int"):  # aspon 1 operand neni int:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.value = int(sym2Value) * int(var.GetValue())

    elif instruction.opCodeType == OpCodes.IDIV:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.value, var.type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if not (var.GetType() == "int" and sym2Type == "int"):  # aspon 1 operand neni int:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.value = int(var.GetValue()) + int(sym2Value)

    elif instruction.opCodeType in [OpCodes.LT, OpCodes.GT]:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != sym2Type or sym1Type not in ["int", "string", "bool"]:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "bool"
        if instruction.opCodeType == OpCodes.LT:
            if sym1Type == "int":
                var.value = "true" if int(sym1Value) < int(sym2Value) else "false"
            elif sym1Type == "bool":
                var.value = "true" if sym1Value == "false" and sym2Value == "true" else "false"
            else:
                var.value = "true" if str(sym1Value) < str(sym2Value) else "false"
        else:
            if sym1Type == "int":
                var.value = "true" if int(sym1Value) > int(sym2Value) else "false"
            elif sym1Type == "bool":
                var.value = "true" if sym1Value == "true" and sym2Value == "false" else "false"
            else:
                var.value = "true" if str(sym1Value) > str(sym2Value) else "false"

    elif instruction.opCodeType == OpCodes.EQ:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != sym2Type or sym1Type not in ["int", "string", "bool", "nil"]:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "bool"
        if sym1Type == "int":
            var.value = "true" if int(sym1Value) == int(sym2Value) else "false"
        else:
            var.value = "true" if str(sym1Value) == str(sym2Value) else "false"

    elif instruction.opCodeType in [OpCodes.AND, OpCodes.OR]:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != sym2Type or sym1Type != "bool":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "bool"
        if instruction.opCodeType == OpCodes.AND:
            var.value = "true" if str(sym1Value) == "true" and str(sym2Value) == "true" else "false"
        else:
            var.value = "false" if str(sym1Value) == "false" and str(sym2Value) == "false" else "true"

    elif instruction.opCodeType == OpCodes.NOT:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        if sym1Type != "bool":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "bool"
        var.value = "true" if str(sym1Value) == "false" else "false"

    elif instruction.opCodeType == OpCodes.STRI2INT:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != "string" or sym2Type != "int":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "string"
        index = int(sym2Value)
        if len(str(sym1Value)) > index >= 0:
            var.value = ord(str(sym1Value)[index])
        else:
            raise ReturnException("Index je mimo rozsah!", RetCodes.wrongString)

    elif instruction.opCodeType == OpCodes.READ:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        if sym1Value == "int":
            var.type = "int"
            try:
                if checker.inputArg == sys.stdin:
                    var.value = str(int(input()))
                else:
                    with open(checker.inputArg) as f:
                        var.value = str(int(f.read()))
            except ValueError as e:
                var.value = "0"
        elif sym1Value == "string":
            var.type = "string"
            try:
                if checker.inputArg == sys.stdin:
                    var.value = str(input())
                else:
                    with open(checker.inputArg) as f:
                        var.value = str(f.read())
            except ValueError as e:
                var.value = ""
        else:
            var.type = "bool"
            if checker.inputArg == sys.stdin:
                var.value = "true" if str(input()).lower() == "true" else "false"
            else:
                with open(checker.inputArg) as f:
                    var.value = "true" if str(f.read()).lower() == "true" else "false"

    elif instruction.opCodeType == OpCodes.CONCAT:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != sym2Type or sym1Type != "string":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "string"
        var.value = sym1Value+sym2Value

    elif instruction.opCodeType == OpCodes.STRLEN:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        if sym1Type != "string":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "int"
        var.value = str(len(sym1Value))

    elif instruction.opCodeType == OpCodes.GETCHAR:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != "string" or sym2Type != "int":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        var.type = "string"
        if len(sym1Value) > int(sym2Value) >= 0:
            var.value = sym1Value[int(sym2Value)]
        else:
            raise ReturnException("Index mimo povoleny rozsah", RetCodes.wrongString)

    elif instruction.opCodeType == OpCodes.SETCHAR:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type != "int" or sym2Type != "string" or var.type != "string":
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)
        if len(var.value) > int(sym1Value) >= 0 and sym2Value != "":
            var.value[int(sym1Value)] = sym2Value[0]
        else:
            raise ReturnException("Index mimo povoleny rozsah", RetCodes.wrongString)

    elif instruction.opCodeType == OpCodes.TYPE:
        var = frames.FindVar(instruction.args[0].name, instruction.args[0].frame)
        var.type = "string"
        done = False
        try:
            sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        except ReturnException as e:
            if e.retCode == RetCodes.missingValue:
                var.value = ""
                done = True
            else:
                raise e
        if not done:
            var.value = sym1Type

    elif instruction.opCodeType == OpCodes.JUMP:
        instructionCnt = program.instructions.index(program.GetLabel(instruction.args[0].value))

    elif instruction.opCodeType == OpCodes.JUMPIFEQ:
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type == sym2Type and sym1Value == sym2Value:
            instructionCnt = program.instructions.index(program.GetLabel(instruction.args[0].value))
        else:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)

    elif instruction.opCodeType == OpCodes.JUMPIFNEQ:
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[1])
        sym2Value, sym2Type = Converter.GetValueAndType(instruction.args[2])
        if sym1Type == sym2Type:
            if sym1Value != sym2Value:
                instructionCnt = program.instructions.index(program.GetLabel(instruction.args[0].value))
        else:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)

    elif instruction.opCodeType == OpCodes.JUMPIFNEQ:
        sym1Value, sym1Type = Converter.GetValueAndType(instruction.args[0])
        if sym1Type == "int":
            if 0 <= int(sym1Value) <= 49:
                exit(int(sym1Value))
            else:
                raise ReturnException("Spatna navratova hodnota!", RetCodes.wrongOpValue)
        else:
            raise ReturnException(RetCodes.messageWrongOps, RetCodes.wrongOps)

    elif instruction.opCodeType == OpCodes.DPRINT:
        tupleValueType = Converter.GetValueAndType(instruction.args[0])
        if tupleValueType[1] == "int":
            print(sys.stderr, int(tupleValueType[0]), end='')
        else:
            print(sys.stderr, str(tupleValueType[0]), end='')

    elif instruction.opCodeType == OpCodes.BREAK:
        print(sys.stderr, "instructionCnt: " + instructionCnt)


    elif instruction.opCodeType in [OpCodes.LABEL]:
        pass

    instructionCnt+=1

    #globalne chytat i nepovedene otevreni souboru