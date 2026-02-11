<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    th { background: #f3f3f3; }
  </style>
</head>
<body>
  <h3>Export: {{ $tableName }}</h3>

  <table>
    <thead>
      <tr>
        @foreach($columns as $col)
          <th>{{ ucwords(str_replace('_',' ', $col)) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $row)
        @php($r = (array) $row)
        <tr>
          @foreach($columns as $col)
            <td>{{ $r[$col] ?? '' }}</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
