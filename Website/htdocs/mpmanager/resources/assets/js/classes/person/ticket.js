import { Paginator } from '../paginator'
import { resources } from '../../resources'

export class Ticket {
    constructor () {
        this.id = null
        this.name = null
        this.description = null
        this.due = null
        this.closed = null
        this.lastActivity = null
        this.comments = []
        this.category = null
        this.created_at = null
    }

    fromJson (ticketData) {
        let ticket = ticketData.ticket
        let actions = ticketData.actions
        this.created = ticketData.created_at
        this.id = ticket.id
        this.name = ticket.name
        this.description = ticket.desc
        this.due = ticket.due
        this.lastActivity = ticket.dateLastActivity
        this.category = ticketData.category
        this.closed = ticket.closed
        this.status =ticketData.status
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

    close () {
        axios.delete(resources.ticket.close, {data: {'ticketId': this.id}}).then(() => {
            this.closed = true
        })
    }
}

export class UserTickets {
    constructor (personId) {
        this.list = []
        this.paginator = new Paginator(resources.ticket.getUser + personId)
    }

    addTicket (ticket) {
        this.list.push(ticket)
    }

    search () {
    // this.paginator = new Paginator(resources.meters.search);
    // EventBus.$emit('loadPage', this.paginator, {'term': term});
    }

    showAll () {
    //this.paginator = new Paginator(resources.meters.list);
    //EventBus.$emit('loadPage', this.paginator);
    }

    updateList (data) {

        this.list = []

        for (let m in data) {
            let ticket = new Ticket()
            ticket.fromJson(data[m])
            this.list.push(ticket)
        }

    }

    newComment (commentData) {
        axios.post(resources.ticket.comments, commentData)
    }

}
