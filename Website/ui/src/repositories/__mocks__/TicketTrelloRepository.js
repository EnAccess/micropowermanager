const ticketTrelloDetailResponse = require('./responses/ticket/ticketTrelloDetail.json')

export default {

    detail(){
        return new Promise((resolve) => {
            process.nextTick(() =>
                resolve(ticketTrelloDetailResponse)
            )

        })
    }

}
