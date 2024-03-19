<?php

declare(strict_types=1);

const EXAMPLE_IF_TEMPLATE = "<div>\n    {% if True %}\n        yay\n    {% endif %}\n</div>";
const EXAMPLE_FOR_TEMPLATE = "{% for item in seq %}\n    {{ item }}\n{% endfor %}";
const EXAMPLE_FOR_TEMPLATE_2 = "{% for item in seq -%}\n    {{ item }}\n{% endfor %}";
const EXAMPLE_FOR_TEMPLATE_3 = "{% for item in seq %}\n    {{ item }}\n{%- endfor %}";
const EXAMPLE_FOR_TEMPLATE_4 = "{% for item in seq -%}\n    {{ item }}\n{%- endfor %}";
const EXAMPLE_COMMENT_TEMPLATE = "    {# comment #}\n  {# {% if true %} {% endif %} #}\n";

dataset('interpreterTestData', [
    // If tests
    'if (no strip or trim)' => [
        'template' => EXAMPLE_IF_TEMPLATE,
        'data' => [],
        'lstrip_blocks' => false,
        'trim_blocks' => false,
        'target' => "<div>\n    \n        yay\n    \n</div>",
    ],
//   'if (strip blocks only)'  => [
//        'template' => EXAMPLE_IF_TEMPLATE,
//        'data' => [],
//        'lstrip_blocks' => true,
//        'trim_blocks' => false,
//        'target' => "<div>\n\n        yay\n\n</div>",
//    ],
    'if (trim blocks only)' => [
        'template' => EXAMPLE_IF_TEMPLATE,
        'data' => [],
        'lstrip_blocks' => false,
        'trim_blocks' => true,
        'target' => "<div>\n            yay\n    </div>",
    ],
//    'if (strip and trim blocks)' => [
//        'template' => EXAMPLE_IF_TEMPLATE,
//        'data' => [],
//        'lstrip_blocks' => true,
//        'trim_blocks' => true,
//        'target' => "<div>\n        yay\n</div>",
//    ],


    // For tests
   'for (no strip or trim)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => false,
        'trim_blocks' => false,
        'target' => "\n    1\n\n    2\n\n    3\n\n    4\n\n    5\n\n    6\n\n    7\n\n    8\n\n    9\n",
    ],
    'for (lstrip blocks only)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => true,
        'trim_blocks' => false,
        'target' => "\n    1\n\n    2\n\n    3\n\n    4\n\n    5\n\n    6\n\n    7\n\n    8\n\n    9\n",
    ],
    'for (trim blocks only)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => false,
        'trim_blocks' => true,
        'target' => "    1\n    2\n    3\n    4\n    5\n    6\n    7\n    8\n    9\n",
    ],
    'for (strip and trim blocks)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => true,
        'trim_blocks' => true,
        'target' => "    1\n    2\n    3\n    4\n    5\n    6\n    7\n    8\n    9\n",
    ],
    'for (single line output with no strip or trim)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE_2,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => false,
        'trim_blocks' => false,
        'target' => "1\n2\n3\n4\n5\n6\n7\n8\n9\n",
    ],
    'for (single line output, no strip or trim 2)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE_3,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => false,
        'trim_blocks' => false,
        'target' => "\n    1\n    2\n    3\n    4\n    5\n    6\n    7\n    8\n    9",
    ],
    'for (single line output, trim only)' =>  [
        'template' => EXAMPLE_FOR_TEMPLATE_3,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => false,
        'trim_blocks' => true,
        'target' => "    1    2    3    4    5    6    7    8    9",
    ],
    'for (single line output, no strip or trim 3)' =>  [
        'template' =>
            EXAMPLE_FOR_TEMPLATE_4,
        'data' => ['seq' => range(1, 9)],
        'lstrip_blocks' => false,
        'trim_blocks' => false,
        'target' => "123456789",
    ],

    // Comment tests
    'comment (basic)' => [
        'template' => EXAMPLE_COMMENT_TEMPLATE,
        'data' => [],
        'lstrip_blocks' => false,
        'trim_blocks' => false,
        'target' => "    \n  ",
    ],
//    'comment (lstrip only)' => [
//        'template' => EXAMPLE_COMMENT_TEMPLATE,
//        'data' => [],
//        'lstrip_blocks' => true,
//        'trim_blocks' => false,
//        'target' => "\n",
//    ],
//    'comment (trim only)' => [
//        'template' => EXAMPLE_COMMENT_TEMPLATE,
//        'data' => [],
//        'lstrip_blocks' => false,
//        'trim_blocks' => true,
//        'target' => "      ",
//    ],
//    'comment (lstrip and trim)' => [
//        'template' => EXAMPLE_COMMENT_TEMPLATE,
//        'data' => [],
//        'lstrip_blocks' => true,
//        'trim_blocks' => true,
//        'target' => "",
//    ],
]);