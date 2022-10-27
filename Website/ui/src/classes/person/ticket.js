import { Paginator } from '../paginator'
import { resources } from '@/resources'

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

        console.log('from json ', ticketData)

        let comments = ticketData?.comments
        this.created = ticketData.created_at
        this.id = ticketData.id
        this.title = ticketData.title
        this.description = ticketData.content
        this.due = ticketData.due_date
        this.category = ticketData.category.label_name
        this.closed = ticketData.status === 1
        this.status = ticketData.status

        if (comments) {
            console.log('COMMENTS FOUND for ' + ticketData.title)

            const commentList = comments.map(function (comment) {
                return {
                    'comment': comment.comment,
                    'date': comment.created_at,
                    'username': comment.ticket_user.user_name,
                }
            })
            this.comments = commentList
            console.log('FINAL COMMENTS', commentList)
        }

        return this
    }

    commentCount () {
        return this.comments.length
    }

    close () {
        axios.delete(resources.ticket.close, { data: { 'ticketId': this.id } }).then(() => {
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
        console.log('update list with ', data)

        if (('data' in data)) {
            this.list = data.data.map(function (ticket) {
                return (new Ticket()).fromJson(ticket)
            })
        }
    }

    newComment (commentData) {
        axios.post(resources.ticket.comments, commentData)
    }

}
