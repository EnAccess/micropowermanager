import { Paginator } from './paginator'
import { resources } from '@/resources'

export class Ticket {
    constructor () {
        this.created = null
        this.id = null
        this.name = null
        this.description = null
        this.due = null
        this.closed = null
        this.lastActivity = null
        this.comments = []
        this.category = null
        this.owner = []
        this.assigned = null
    }

    async getTicketDetail (ticketData) {
        return await
        this.getDetail(ticketData.ticket_id)
    }

    fromJson (ticketData) {
        ticketData = ticketData.data
        let ticket = ticketData.ticket
        let actions = ticketData.actions

        if (ticket === null) {
            return null
        }
        this.assigned = ticketData.assigned_to
        this.closed = ticketData.status === 1
        this.id = ticket.id
        this.created = ticketData.created_at
        this.name = ticket.name
        this.description = ticket.desc
        this.due = ticket.due
        this.lastActivity = ticket.dateLastActivity
        this.category = ticketData.category
        this.owner = ticketData.owner

        for (let i = 0; i < actions.length; i++) {
            let action = actions[i]
            if (action.type !== 'commentCard') {
                continue
            }

            this.comments.push({
                'comment': action.data.text,
                'date': action.date,
                'fullName': action.memberCreator.fullName,
                'username': action.memberCreator.username,
            })
        }
        return this
    }

    commentCount () {
        return this.comments.length
    }

    async getDetail (trelloId) {
        return axios.get(resources.ticket.detail + trelloId).then(response => {
            return this.fromJson(response.data)
        })

    }

    close () {
        axios.delete(resources.ticket.close, {data: {'ticketId': this.id}}).then(() => {
            this.closed = true
        })
    }
}

export class Tickets {
    constructor () {
        this.openedList = []
        this.closedList = []
        this.openedPaginator = new Paginator(resources.ticket.list + '?status=0')
        this.closedPaginator = new Paginator(resources.ticket.list + '?status=1')
    }

    addTicket (ticket) {
        this.openedList.push(ticket)
    }

    search () {
    // this.paginator = new Paginator(resources.meters.search);
    // EventBus.$emit('loadPage', this.paginator, {'term': term});
    }

    showAll () {
    //this.paginator = new Paginator(resources.meters.list);
    //EventBus.$emit('loadPage', this.paginator);
    }

    async updateList (data, type) {

        if (type === 'ticketListOpened')
            this.openedList = []
        else
            this.closedList = []

        for (let m in data) {
            let ticket = new Ticket()
            let ticketData = await ticket.getTicketDetail(data[m])
            ticketData.assignedTo = data[m].assigned_to
            if (type === 'ticketListOpened')
                if (ticketData !== null)
                    this.openedList.push(ticketData)
                else {
                    if (ticketData !== null) {
                        this.closedList.push(ticketData)
                    }
                }
        }
    }
}
