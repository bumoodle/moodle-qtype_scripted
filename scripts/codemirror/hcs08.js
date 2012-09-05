CodeMirror.defineMode("hcs08", function(config) {
  function words(str) {
    var obj = {}, words = str.split(" ");
    for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
    return obj;
  }
  var keywords = words("adc add ais aix and asl asla aslx asr asra asrx bcc bclr bcs beq bge bgnd bgt bhcc bhcs bhi bhs bih bil bit ble blo bls blt bmc bmi bms bne bpl bra brclr brn brset bset bsr cbeq cbeqa cbeqx clc cli clr clra clrx clrh cmp com coma comx cphx cpx daa dbnz dbnza dbnzx dc dc.b dc.w dec deca decx div ds ds.b ds.w eor equ inc inca incx jmp jsr lda ldhx ldx lsl lsla lslx lsr lsra lsrx mov mul neg nega negx nop nsa ora org psh psha pshx pshh pul pula pulx pulh rol rola rolx ror rora rorx rsp rti rts sbc sec sei sta sthx stop stx sub swi tap tax tpa tst tsta tstx tsx txa txs wait dc dc.b dc.w");
  var blockKeywords = words("case do else for if switch while struct then of");
  var atoms = {"null": true};

  var isOperatorChar = /[+\-*&%=<>!?|\/]/;
  var curPunc;

  function tokenBase(stream, state) {
    var ch = stream.next();
    if (ch == "#") {
      stream.skipToEnd();
      return "immediate";
    }
    if (ch == '"' || ch == "'") {
      state.tokenize = tokenString(ch);
      return state.tokenize(stream, state);
    }
    if (ch == "(" && stream.eat("*")) {
      state.tokenize = tokenComment;
      return tokenComment(stream, state);
    }
    if (/[\[\]{}\(\),\:\.]/.test(ch)) {
      curPunc = ch;
      return null
    }
    if (/\d/.test(ch)) {
      stream.eatWhile(/[\w\.]/);
      return "number";
    }
    if (ch == ";") {
        stream.skipToEnd();
        return "comment";
    }
    if (isOperatorChar.test(ch)) {
      stream.eatWhile(isOperatorChar);
      return "operator";
    }
    stream.eatWhile(/[\w\$_]/);
    var cur = stream.current().toLowerCase();
    if (keywords.propertyIsEnumerable(cur)) {
      if (blockKeywords.propertyIsEnumerable(cur)) curPunc = "newstatement";
      return "builtin";
    }
    if (atoms.propertyIsEnumerable(cur)) return "atom";
    return "word";
  }

  function tokenString(quote) {
    return function(stream, state) {
      var escaped = false, next, end = false;
      while ((next = stream.next()) != null) {
        if (next == quote && !escaped) {end = true; break;}
        escaped = !escaped && next == "\\";
      }
      if (end || !escaped) state.tokenize = null;
      return "string";
    };
  }

  function tokenComment(stream, state) {
    var maybeEnd = false, ch;
    while (ch = stream.next()) {
      if (ch == ")" && maybeEnd) {
        state.tokenize = null;
        break;
      }
      maybeEnd = (ch == "*");
    }
    return "comment";
  }

  function Context(indented, column, type, align, prev) {
    this.indented = indented;
    this.column = column;
    this.type = type;
    this.align = align;
    this.prev = prev;
  }
  function pushContext(state, col, type) {
    return state.context = new Context(state.indented, col, type, null, state.context);
  }
  function popContext(state) {
    var t = state.context.type;
    if (t == ")" || t == "]" )
      state.indented = state.context.indented;
    return state.context = state.context.prev;
  }

  // Interface

  return {
    startState: function(basecolumn) {
      return {
        tokenize: null,
        context: new Context((basecolumn || 0) - config.indentUnit, 0, "top", false),
        indented: 0,
        startOfLine: true
      };
    },

    token: function(stream, state) {
      var ctx = state.context;
      if (stream.sol()) {
        if (ctx.align == null) ctx.align = false;
        state.indented = stream.indentation();
        state.startOfLine = true;
      }
      if (stream.eatSpace()) return null;
      curPunc = null;
      var style = (state.tokenize || tokenBase)(stream, state);
      if (style == "comment" || style == "meta") return style;
      if (ctx.align == null) ctx.align = true;

      if ((curPunc == ";" || curPunc == ":") && ctx.type == "statement") popContext(state);
      else if (curPunc == "[") pushContext(state, stream.column(), "]");
      else if (curPunc == "(") pushContext(state, stream.column(), ")");
      else if (curPunc == ctx.type) popContext(state);
      else if ( ctx.type == "top" || (ctx.type == "statement" && curPunc == "newstatement"))
        pushContext(state, stream.column(), "statement");
      state.startOfLine = false;
      return style;
    },

    electricChars: "{}"
  };
});

CodeMirror.defineMIME("text/asm-hcs08", "hcs08");
