<?php
require_once 'function.nsformat.php';

echo '<pre>';

//nsformatSetLocaleCode('pl-PL');

echo nsformat("1: standard text\n");
echo nsformat("2: standard param {0} and {@0} and inline {INLINE VALUE}, {=12}\n", 'VALUE');
echo nsformat("3: empty value {0}, {0:~empty}, {1:~---}\n", '', 0);
echo nsformat("4a: formats {0:%.3f}; {0:%d}; {0:10a}; {1:num}; {1:int}; {1:h}; {=198212.2131:num}\n", 4.31, 17234.43);
echo nsformat("4b: formats a:{0:0-10s}; b:{1:0-10s}; c:{0:-0-10s}; d:{0:+10-20s}; e:{0:**10s}; f:{1:--25-100s}; f2:{1:+-25-100s}; g:{2:+_7-8.2n}; h:{2:07-10.2n}; i:{2:--7.2n}\n", 'ala', 'abcdefghijklmnopqrstu', 12.5);
echo nsformat("4c: formats {0:y}; {0:y yes;no}; {1:y}; {1:y yes;no};\n", false, true);
echo nsformat("4d: exception formats {0:p}\n", new Exception('test exception'));
echo nsformat("4e: auto formats {0:a} {1:a} {2:a} {3:a}\n", false, 12, 12333.321, 'text');
echo nsformat("4f: auto formats {0:a} {1:a}\n", [1,2,3,4], new Exception("auto"));
echo nsformat("5: string formats {0:s}; {0:s uc}; {0:s ucf}; {0:s caps}\n", 'lorem ipsum dolor');
echo nsformat("6: struct {0:s}; {0:s json}; {0:s php}; {0:s dump}; {0:s join ,}\n", [1,2,3,4,[5,6]]);
echo nsformat("7: struct idxs {0.aaa}; {.bbb:-10s}; {1.index}\n", ['aaa' => 'AAA', 'bbb' => 'BBB'], ['index' => 'III']);
echo nsformat("8: escaping {{this_is_escaped_tag}} {{0}} {0:~empty}}:s}\n", null);

$compiled = nsformatCompile("C1: compiled {{esc_tag}} {{0}} {0:~empty}}:s} {inline:10s} {1:num}\n");
echo nsformat($compiled, null, 123456789);