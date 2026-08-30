

/*
* Отрисовка формы
* type - парамер определя   0 - форма если задача предозначена для сотрудников
*                           1 - форма если задача предозначена для клинета
*/
function addFormView(type = false) {
    let content = `
    <div class="form-add-event flex horizon center"> 
        <div>
        <form>
            <input type="hidden" name="" value="">
            <input type="hidden" name="" value="">
            <input type="hidden" name="" value="">
            <hr>
            <div>
                <p><a href="#">Задача</a> до <a href="#">завтра</a> для <a href="#">меня</a></p>
            </div>
            <textarea name="" rows="9"></textarea>
        </form>
        </div>

    </div>
    `;

    return content;
}