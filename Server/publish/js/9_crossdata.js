function print_crossdata_config() {
  var html = '';
  html += '<style>\
           .dropdown-menu {\
            display: none;\
            position: absolute;\
            z-index: 1000;\
            background-color: white;\
            border: 1px solid #ccc;\
            max-height: 200px;\
            overflow-y: auto;\
            width: 100%;\
          }\
          /* Показ dropdown */\
          .dropdown-menu.show {\
            display: block;\
          }\
          /* Подсветка элементов при наведении */\
          .dropdown-item:hover {\
            background-color: #f1f1f1;\
            cursor: pointer;\
          }</style>\
  <form id="crossdata-form" enctype="multipart/form-data">\
            <div class="form-group row">\
                <label for="brand" class="col-sm-2 col-form-label">Выберите бренд:</label>\
                <div class="col-sm-10">';

  html += '<input list="brand-list" class="form-control" id="brand" name="brand" placeholder="Введите бренд">';
  html += '<div id="brand-list" class="dropdown-menu"></div>';
  html += '<input type="hidden" id="select-brand-id" name="select-brand-id">';

  html += '</div></div>';
  html += '<div class="form-group row">\
              <label for="action" class="col-sm-2 col-form-label">Выберите действие:</label>\
              <div class="col-sm-10">\
                  <select class="form-control" id="action" name="action" onchange="toggleActionButtons()">\
                      <option value="" hidden>Выберите действие</option>\
                      <option value="upload">Загрузить список деталей</option>\
                      <option value="add_cross">Загрузить список кроссов</option>\
                      <option value="add_params">Загрузить параметры к деталям</option>\
                      <option value="add_images">Загрузить изображения к деталям</option>\
                      <option value="delete">BLACKLIST кроссов</option>\
                  </select>\
              </div>\
          </div>';

  html += '<div class="form-group row" id="fileUploadSection" style="display: none;">\
            <label for="file" class="col-sm-2 col-form-label">Загрузите файл:</label>\
            <div class="col-sm-10">\
                <input type="file" class="form-control-file" id="file" name="file" accept=".xls, .xlsx">\
            </div>\
        </div>\
        <div class="form-group row" id="yandexLinkSection" style="display: none;">\
            <label for="yandexLink" class="col-sm-2 col-form-label">Папка на Яндекс.Диск:</label>\
            <div class="col-sm-10">\
                <input type="url" class="form-control" id="yandexLink" name="yandexLink" placeholder="Введите папку на Яндекс.Диск. (/MEGAPOWER)">\
            </div>\
        </div>\
        <div class="form-group row" id="actionButtons" style="display: none;">\
            <div class="col-sm-10 offset-sm-2">\
                <button type="button" class="btn btn-primary" onclick="handleFileUpload()">Сформировать таблицу</button>\
                <button type="button" class="btn btn-success" id="loadCategoriesButton">Загрузить</button>\
            </div>\
        </div>\
        <div class="form-group row" id="actionInageButtons" style="display: none;">\
            <div class="col-sm-10 offset-sm-2">\
                <button type="button" class="btn btn-success" id="loadImagesButton">Загрузить фотографии</button>\
            </div>\
        </div>';
  $("#crossdata_config").html(html);

  // document.getElementById('brand').addEventListener('input', );
  // document.getElementById('brand').addEventListener('change', handleBrandChange);
  document.getElementById('loadImagesButton').addEventListener('click', showConfirmImagesModal);
  document.getElementById('loadImagesButton').addEventListener('click', uploadImages);
  document.getElementById('crossdata_detail_table').innerHTML = "";
  $(document).ready(function () {
    $('#brand').on('input', updateBrandList);
  });
}

function toggleActionButtons() {
  var action = document.getElementById('action').value;
  var fileUploadSection = document.getElementById('fileUploadSection');
  var yandexLinkSection = document.getElementById('yandexLinkSection');
  var actionButtons = document.getElementById('actionButtons');
  var actionInageButtons = document.getElementById('actionInageButtons');

  // Скрыть все дополнительные секции по умолчанию
  fileUploadSection.style.display = "none";
  yandexLinkSection.style.display = "none";
  actionButtons.style.display = "none";
  actionInageButtons.style.display = "none";
  document.getElementById('crossdata_detail_table').innerHTML = "";

  if (action === "upload") {
    resetButtonListeners();
    document.getElementById('loadCategoriesButton').addEventListener('click', showCategoryModal);
    fileUploadSection.style.display = "block";
    actionButtons.style.display = "block";
    actionInageButtons.style.display = "none";
  } else if (action === "add_images") {
    yandexLinkSection.style.display = "block";
    actionButtons.style.display = "none";
    actionInageButtons.style.display = "block";
  } else if (action === "add_cross" || action === "delete" || action === "add_params") {
    resetButtonListeners();
    document.getElementById('loadCategoriesButton').addEventListener('click', uploadData);
    fileUploadSection.style.display = "block";
    yandexLinkSection.style.display = "none";
    actionButtons.style.display = "block";
    actionInageButtons.style.display = "none";
  } else {
    fileUploadSection.style.display = "none";
    actionButtons.style.display = "none";
    actionInageButtons.style.display = "none";
  }
}

function resetButtonListeners() {
  loadCategoriesButton.removeEventListener('click', showCategoryModal);
  loadCategoriesButton.removeEventListener('click', uploadData);
}

function updateBrandList() {
  var input = document.getElementById('brand');
  var query = input.value;
  var dropdown = document.getElementById('brand-list');

  if (query.length < 2) {
    dropdown.classList.remove('show'); // Скрываем dropdown, если введено менее 2 символов
    dropdown.innerHTML = ''; // Очищаем dropdown
    return;
  }

  var send = { brand: query };

  api_query_array("/api/index.php", send, "search_brand_crossdata").then(function (data) {
    dropdown.innerHTML = '';
    if (data.brands && data.brands.length > 0) {
      data.brands.forEach(function (brand) {
        var item = document.createElement('div');
        item.className = 'dropdown-item';
        item.textContent = brand.brand;
        item.setAttribute('data-id', brand.brand_id); // Устанавливаем data-id на элемент
        item.addEventListener('click', function () {
          input.value = brand.brand;
          input.setAttribute('data-id', brand.brand_id); // Устанавливаем data-id в поле ввода
          document.getElementById('select-brand-id').value = brand.brand_id;
          dropdown.classList.remove('show'); // Скрываем dropdown после выбора
          dropdown.innerHTML = ''; // Очищаем dropdown после выбора
        });
        dropdown.appendChild(item);
      });
      dropdown.classList.add('show'); // Показываем dropdown, если есть результаты
    } else {
      dropdown.classList.remove('show'); // Скрываем dropdown, если нет результатов
    }
  }).catch(function (error) {
    console.error("Ошибка при запросе данных:", error);
  });
}

function handleBrandChange() {
  var input = document.getElementById('brand');
  var options = document.getElementById('brand-list').options;
  var selectedBrandId = null;

  // Найти выбранный бренд в списке опций datalist
  for (var i = 0; i < options.length; i++) {
    if (options[i].value === input.value) {
      selectedBrandId = options[i].getAttribute('data-id');
      break;
    }
  }
}

var fileData = null;
var fileBlob = null;

function handleFileUpload() {
  var brandId = document.getElementById('select-brand-id').value;
  var action = document.getElementById('action').value;
  var fileInput = document.getElementById('file');

  if (!brandId || !action || !fileInput.files.length) {
    alert('Пожалуйста, заполните все обязательные поля и загрузите файл.');
    return;
  }

  $.blockUI({
    css: {
      border: 'none',
      padding: '15px',
      backgroundColor: '#000',
      '-webkit-border-radius': '10px',
      '-moz-border-radius': '10px',
      opacity: .5,
      color: '#fff'
    },
    message: 'Парсим файл...'
  });

  var file = fileInput.files[0];
  var reader = new FileReader();

  document.getElementById('crossdata_detail_table').innerHTML = "";
  fileBlob = fileInput.files[0];

  reader.onload = function (event) {
    fileData = event.target.result; // Сохраняем данные файла
    var data = event.target.result;

    var fileType = file.name.split('.').pop().toLowerCase();
    if (fileType === 'xls' || fileType === 'xlsx') {
      parseXLSX(data, action);
    }
    else {
      alert("Загрузите Excel файл");
      $.unblockUI();
    }
  };

  reader.readAsBinaryString(file); // Чтение файла как бинарной строки
}

var headerOptions = {};

function parseCSV(data) {
  var lines = data.split('\n');
  var outputHtml = '<table class="table table-bordered table-striped"><thead><tr>';

  // Parse headers and create select dropdowns
  var headers = lines[0].split(',');
  headers.forEach(function (header, index) {
    outputHtml += '<th>';
    outputHtml += '<select class="form-control" id="headerSelect_' + index + '" onclick="filterOptions(this)">';

    var matched = false;
    var selectedOptions = [];

    // Determine selected option
    for (var option in headerOptions) {
      if (header === option) {
        outputHtml += '<option value="' + headerOptions[option] + '" selected hidden>' + option + '</option>';
        matched = true;
      } else {
        selectedOptions.push(option);
      }
    }

    if (!matched) {
      outputHtml += '<option value="' + headerOptions['Пропустить'] + '" selected hidden>Пропустить</option>';
    } else {
      selectedOptions.push('Пропустить');
    }

    // Add other options
    selectedOptions.forEach(function (option) {
      outputHtml += '<option value="' + headerOptions[option] + '">' + option + '</option>';
    });

    outputHtml += '</select>';
    outputHtml += '</th>';
  });
  outputHtml += '</tr></thead><tbody>';

  // Parse data rows
  for (var i = 1; i < Math.min(31, lines.length); i++) {
    var cells = lines[i].split(',');
    outputHtml += '<tr>';
    cells.forEach(function (cell) {
      outputHtml += '<td>' + cell + '</td>';
    });
    outputHtml += '</tr>';
  }
  outputHtml += '</tbody></table>';

  document.getElementById('crossdata_detail_table').innerHTML += outputHtml;
  $.unblockUI();
}

function parseXLSX(data, action) {
  var workbook = XLSX.read(data, { type: 'binary' });
  var sheet = workbook.Sheets[workbook.SheetNames[0]];
  var range = XLSX.utils.decode_range(sheet['!ref']);
  var outputHtml = '<table class="table table-bordered table-striped"><thead><tr>';

  if (action === 'add_cross') {
    headerOptions = {
      'Артикул': 'article',
      'Бренд': 'brand',
      'Артикул OEM': 'articleOem',
      'Бренд OEM': 'brandOem',
      'Удаление': 'delete',
      'Наименование': 'name',
      'Пропустить': 'skip'
    };
  }
  else if (action === "add_params") {
    headerOptions = {
      'Артикул': 'article'
    };
  }
  else if (action === "delete") {
    headerOptions = {
      'Артикул': 'article',
      'Бренд': 'brand',
      'Артикул OEM': 'articleOem',
      'Бренд OEM': 'brandOem',
      'Пропустить': 'skip'
    };
  }
  else {
    headerOptions = {
      'Наименование': 'name',
      'Артикул': 'article',
      'Категория': 'category',
      'Пропустить': 'skip'
    };
  }

  // Парсинг заголовков и создание выпадающих списков
  for (var col = range.s.c; col <= range.e.c; col++) {
    var cell = sheet[XLSX.utils.encode_cell({ r: 0, c: col })];
    var header = cell ? cell.v : '';
    outputHtml += '<th>';
    outputHtml += '<select class="form-control headerSelect custom-header-select" id="headerSelect_' + col + '" onclick="filterOptions(this)">';

    var matched = false;
    var selectedOptions = [];

    // Определение выбранной опции
    for (var option in headerOptions) {
      if (header === option || header.includes(option)) {
        outputHtml += '<option value="' + headerOptions[option] + '" selected>' + option + '</option>';
        matched = true;
      } else {
        selectedOptions.push(option);
      }
    }

    if (!matched) {
      outputHtml += '<option value="' + headerOptions['Пропустить'] + '" selected hidden>Пропустить</option>';
    }

    selectedOptions.forEach(function (option) {
      outputHtml += '<option value="' + headerOptions[option] + '">' + option + '</option>';
    });

    outputHtml += '</select>';
    outputHtml += '</th>';
  }
  outputHtml += '</tr></thead><tbody>';

  for (var row = 1; row <= Math.min(30, range.e.r); row++) {
    outputHtml += '<tr>';
    for (var col = range.s.c; col <= range.e.c; col++) {
      var cell = sheet[XLSX.utils.encode_cell({ r: row, c: col })];
      outputHtml += '<td>' + (cell ? cell.v : '') + '</td>';
    }
    outputHtml += '</tr>';
  }
  outputHtml += '</tbody></table>';

  document.getElementById('crossdata_detail_table').innerHTML += outputHtml;
  $.unblockUI();
}

function filterOptions() {
  var allSelects = document.querySelectorAll('select.custom-header-select'); // Выбор только элементов с классом custom-header-select
  var selectedValues = new Set();

  allSelects.forEach(function (select) {
    if (select.value !== headerOptions['Пропустить']) {
      selectedValues.add(select.value);
    }
  });

  // Фильтрация опций во всех выпадающих списках
  allSelects.forEach(function (select) {
    var options = select.querySelectorAll('option');
    options.forEach(function (option) {
      if (option.value !== headerOptions['Пропустить']) {
        if (selectedValues.has(option.value)) {
          option.style.display = 'none';
        } else {
          option.style.display = '';
        }
      } else {
        option.style.display = '';
      }
    });
  });
}

function showCategoryModal() {
  if (!fileData) {
    alert('Пожалуйста, загрузите файл.');
    return;
  }

  $.blockUI({
    css: {
      border: 'none',
      padding: '15px',
      backgroundColor: '#000',
      '-webkit-border-radius': '10px',
      '-moz-border-radius': '10px',
      opacity: .5,
      color: '#fff'
    },
    message: 'Обрабатываем файл...'
  });

  var workbook = XLSX.read(fileData, { type: 'binary' });
  var sheet = workbook.Sheets[workbook.SheetNames[0]];
  var range = XLSX.utils.decode_range(sheet['!ref']);

  var selectedIndex = null;
  var selects = document.querySelectorAll('.headerSelect');

  // Найти индекс выбранной колонки "Категория"
  selects.forEach(function (select, index) {
    if (select.value === 'category') {
      selectedIndex = index;
    }
  });

  if (selectedIndex === null) {
    uploadData();
    return;
  }

  var categoryValues = new Set();
  // Найти все уникальные категории в выбранной колонке
  for (var row = 1; row <= range.e.r; row++) {
    var cell = sheet[XLSX.utils.encode_cell({ r: row, c: selectedIndex })];
    if (cell) {
      categoryValues.add(cell.v.trim());
    }
  }

  var existingModal = document.getElementById('categoryModal');
  if (existingModal) {
    existingModal.remove();
  }

  // Создание и отображение модального окна с уникальными категориями
  var modalHtml = '<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel" aria-hidden="true">\
                     <div class="modal-dialog" role="document">\
                       <div class="modal-content">\
                         <div class="modal-header">\
                           <h5 class="modal-title" id="categoryModalLabel">Сопоставление категорий</h5>\
                           <button type="button" class="close" data-dismiss="modal" aria-label="Close">\
                             <span aria-hidden="true">&times;</span>\
                           </button>\
                         </div>\
                         <div class="modal-body">\
                           <form id="categoryMappingForm">';

  var index = 0;
  categoryValues.forEach(function (value) {
    modalHtml += '<div class="form-group row">\
                      <div class="col-sm-4">\
                        <input type="text" class="form-control" value="' + value + '" readonly>\
                      </div>\
                      <div class="col-sm-8">\
                        <input type="text" class="form-control category-input" id="category_' + index + '" name="category_' + index + '" placeholder="Введите категорию" data-original-category="' + value + '">\
                        <div class="dropdown-menu" id="dropdown_' + index + '"></div>\
                      </div>\
                    </div>';
    index++;
  });

  modalHtml += '</form>\
                 </div>\
                 <div class="modal-footer">\
                   <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>\
                   <button type="button" class="btn btn-primary" onclick="uploadData()">Сохранить</button>\
                 </div>\
               </div>\
             </div>\
           </div>';

  document.body.insertAdjacentHTML('beforeend', modalHtml);
  $('#categoryModal').modal('show');
  $.unblockUI();
  // Добавить обработчики ввода для автозаполнения
  document.querySelectorAll('.category-input').forEach(function (input) {
    input.addEventListener('input', handleCategoryInput);
  });
}

function handleCategoryInput(event) {
  var input = event.target;
  var query = input.value;
  var dropdown = document.getElementById('dropdown_' + input.id.split('_')[1]);

  if (query.length < 2) {
    dropdown.classList.remove('show'); // Скрываем dropdown, если введено менее 2 символов
    dropdown.innerHTML = ''; // Очищаем dropdown
    return;
  }

  var send = { name: query };

  api_query_array("/api/index.php", send, "search_categorys_crossdata").then(function (data) {
    dropdown.innerHTML = '';
    if (data.categorys && data.categorys.length > 0) {
      data.categorys.forEach(function (category) {
        var item = document.createElement('div');
        item.className = 'dropdown-item';
        item.textContent = category.name;
        item.setAttribute('data-id', category.id); // Устанавливаем data-id на элемент
        item.addEventListener('click', function () {
          input.value = category.name;
          input.setAttribute('data-id', category.id); // Устанавливаем data-id в поле ввода
          dropdown.classList.remove('show'); // Скрываем dropdown после выбора
          dropdown.innerHTML = ''; // Очищаем dropdown после выбора
        });
        dropdown.appendChild(item);
      });
      dropdown.classList.add('show'); // Показываем dropdown, если есть результаты
    }
  }).catch(function (error) {
    console.error('Ошибка при поиске категорий:', error);
  });
}

function uploadData() {
  if (!fileData) {
    alert('Пожалуйста, загрузите файл.');
    return;
  }

  var brandId = document.getElementById('select-brand-id').value;
  var action = document.getElementById('action').value;

  if (!(fileBlob instanceof Blob)) {
    console.error('Файл не является Blob объектом.');
    return;
  }

  var workbook = XLSX.read(fileData, { type: 'binary' });
  var sheet = workbook.Sheets[workbook.SheetNames[0]];
  var range = XLSX.utils.decode_range(sheet['!ref']);

  var selectedColumnsData = updateSelectedColumns();
  if (!selectedColumnsData) {
    return;
  }

  var selectedColumns = selectedColumnsData.selectedColumns;
  var skipValues = selectedColumnsData.skipValues;

  var categoryMappings = {};
  document.querySelectorAll('.category-input').forEach(function (input) {
    var originalCategory = input.getAttribute('data-original-category');
    var mappedCategory = input.getAttribute('data-id');
    if (mappedCategory) {
      categoryMappings[originalCategory] = mappedCategory;
    }
  });

  $.blockUI({
    css: {
      border: 'none',
      padding: '15px',
      backgroundColor: '#000',
      '-webkit-border-radius': '10px',
      '-moz-border-radius': '10px',
      opacity: .5,
      color: '#fff'
    },
    message: 'Загружаем данные...'
  });

  var formData = new FormData();
  formData.append('file', fileBlob, 'uploaded_file.xlsx');
  formData.append('selected_columns', JSON.stringify(selectedColumns));
  formData.append('skip_values', JSON.stringify(skipValues));
  formData.append('category_mappings', JSON.stringify(categoryMappings));
  formData.append('brand_id', brandId);
  formData.append('action', action);

  fetch('/upload_details_crossdata.php', {
    method: 'POST',
    body: formData
  }).then(response => response.json())
    .then(data => {
      if (data.success) {
        $.unblockUI();
        alert('Данные успешно загружены. Загружено: ' + data.added);
        $('#categoryModal').modal('hide');
        $('#crossdata_detail_table').empty();
        $('input[type="text"], input[type="url"], input[type="file"]').val('');
        $('select').prop('selectedIndex', 0);
        $('#brand, #select-brand-id').val('');
        $('#fileUploadSection, #yandexLinkSection, #actionButtons, #actionInageButtons').hide();
      } else {
        $.unblockUI();
        alert('Ошибка загрузки данных: ' + data.error);
      }
    }).catch(error => {
      $.unblockUI();
      console.error('Ошибка:', error);
    });
}

function updateSelectedColumns() {
  var selectedColumns = {};
  var skipValues = [];

  document.querySelectorAll('.headerSelect').forEach(function (select) {
    var columnType = select.value;
    var columnId = select.id; // Получаем id элемента
    var columnIndex = columnId.split('_')[1]; // Извлекаем номер колонки из id

    if (columnType === 'skip') {
      skipValues.push(columnIndex); // Сохраняем индекс, если выбран skip
    } else if (columnType) {
      selectedColumns[columnType] = columnIndex; // Сохраняем индекс для других типов
    }
  });

  // Определение обязательных типов (без 'brand' и 'skip')
  var requiredColumns = Object.values(headerOptions).filter(function (type) {
    return type !== 'skip' && type !== 'brand' && type !== 'category' && type !== 'delete' && type !== 'name';
  });

  // Проверка, что все обязательные параметры выбраны
  var missingColumns = requiredColumns.filter(function (type) {
    return !selectedColumns[type];
  });

  if (missingColumns.length > 0) {
    var missingNames = missingColumns.map(function (type) {
      for (var key in headerOptions) {
        if (headerOptions[key] === type) {
          return key;
        }
      }
    });
    alert('Пожалуйста, выберите все необходимые параметры: ' + missingNames.join(', '));
    return;
  }

  return {
    selectedColumns: selectedColumns,
    skipValues: skipValues
  };
}

function showConfirmImagesModal() {
  var existingModal = document.getElementById('confirmModal');
  if (existingModal) {
    existingModal.remove();
  }

  // Создание и отображение модального окна с уникальными категориями
  var modalHtml = '<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">\
      <div class="modal-dialog" role="document">\
        <div class="modal-content">\
          <div class="modal-header">\
            <h5 class="modal-title" id="confirmModalLabel">Подтверждение</h5>\
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">\
              <span aria-hidden="true">&times;</span>\
            </button>\
          </div>\
          <div class="modal-body">\
            Вы уверены, что хотите загрузить изображения? Этот процесс может занять некоторое время.\
          </div>\
          <div class="modal-footer">\
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>\
            <button type="button" class="btn btn-primary" onclick="" id="confirmUploadButton">Подтвердить</button>\
          </div>\
        </div>\
      </div>\
    </div>';

  document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function uploadImages() {
  // Получаем значение ссылки из поля ввода
  var yandexLink = document.getElementById('yandexLink').value;
  var brand = document.getElementById('brand').value;

  // Проверка, что ссылка не пустая
  if (!yandexLink) {
    alert('Пожалуйста, введите ссылку на Яндекс.Диск.');
    return;
  }

  // Открытие модального окна подтверждения
  $('#confirmModal').modal('show');

  // Обработка подтверждения в модальном окне
  document.getElementById('confirmUploadButton').onclick = function () {
    $.blockUI({
      css: {
        border: 'none',
        padding: '15px',
        backgroundColor: '#000',
        '-webkit-border-radius': '10px',
        '-moz-border-radius': '10px',
        opacity: .5,
        color: '#fff'
      },
      message: 'Загружаем фотографии...'
    });

    // Подготовка данных для отправки
    var send = [];
    send['link'] = yandexLink;
    send['brand_name'] = brand;

    api_query_array("/api/index.php", send, "upload_images_crossdata").then(function (data) {
      if (data.status === 'ok') {
        $.unblockUI();
        alert(data.message); // Успешное сообщение
      } else {
        // Сообщение об ошибке и список неудачных изображений, если они есть
        var errorMessage = data.message;
        if (data.failed_images && data.failed_images.length > 0) {
          errorMessage += '\nНе удалось загрузить следующие изображения:\n' + data.failed_images.join('\n');
        }
        $.unblockUI();
        alert(errorMessage);
      }
    }).catch(function (error) {
      console.error("Ошибка при запросе данных:", error);
      $.unblockUI();
      alert('Произошла ошибка при загрузке изображений. Пожалуйста, попробуйте снова.');
    });

    // Закрываем модальное окно после начала загрузки
    $('#confirmModal').modal('hide');
  };
}