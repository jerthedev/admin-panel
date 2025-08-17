<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Country Field Demo</title>
  <link rel="stylesheet" href="/build/assets/app.css" />
</head>
<body class="p-6">
  <h1 class="text-2xl font-bold mb-4">Country Field Demo</h1>

  <div data-testid="country-field">
    <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
    <select id="country" name="country" class="mt-1 block w-64 border-gray-300 rounded-md shadow-sm">
      <option value="US">United States</option>
      <option value="CA">Canada</option>
      <option value="GB">United Kingdom</option>
      <option value="FR">France</option>
    </select>
  </div>

  <div data-testid="country-field-searchable" class="mt-6">
    <label class="block text-sm font-medium text-gray-700">Country (Searchable)</label>
    <!-- Simple mock searchable input for UI test -->
    <input type="text" placeholder="Search..." class="mt-1 block w-64 border-gray-300 rounded-md shadow-sm" />
    <ul class="mt-2 list-disc list-inside">
      <li>United States</li>
      <li>United Kingdom</li>
      <li>Canada</li>
      <li>France</li>
    </ul>
  </div>

  <form data-testid="country-field-form" method="post" action="#" class="mt-6">
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <label for="country2" class="block text-sm font-medium text-gray-700">Select Country</label>
    <select id="country2" name="country2" class="mt-1 block w-64 border-gray-300 rounded-md shadow-sm">
      <option value="US">United States</option>
      <option value="CA">Canada</option>
      <option value="GB">United Kingdom</option>
      <option value="FR">France</option>
    </select>

    <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded">Save</button>
  </form>

  <div data-testid="save-success" class="hidden mt-4 text-green-600">Saved!</div>
  <div data-testid="country-display" class="mt-2">United States</div>

  <script>
    document.querySelector('[data-testid="country-field-form"]').addEventListener('submit', function (e) {
      e.preventDefault()
      const select = document.getElementById('country2')
      document.querySelector('[data-testid="country-display"]').textContent = select.options[select.selectedIndex].text
      document.querySelector('[data-testid="save-success"]').classList.remove('hidden')
    })
  </script>
</body>
</html>

