

/*
* Отрисовка формы поиска клиентов
*
*/
function serchClientView(clients) {
    let content = `
    <div>
        <form>
            <input type="text" name="" value="">
            <div>
                <ul>
                    <li>
                        <p>Иванов Иван Иванович, тел: 7(960)055-55-55</p>
                    </li>
                </ul>
            </div>
        </form>
        <div>
            <p>Воспользуйтесь поиском</p>
        </div>
    </div>
    `;

    return content;
}