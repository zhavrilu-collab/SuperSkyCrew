<?php

return [
    'dump_url' => (string) env(
        'NKZ_DUMP_URL',
        'http://web.dzs.hr/App/klasus/Download/GenericFillerFrm.aspx?id=1221980&parent=ClassificationVersion&content=ClassificationItems&action=downloadJson',
    ),
    'timeout' => 90,
];
