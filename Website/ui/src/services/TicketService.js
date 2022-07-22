import Repository from '../repositories/RepositoryFactory'
import {ErrorHandler} from '@/Helpers/ErrorHander'
import {Paginator} from '@/classes/paginator'
import {resources} from '@/resources'
import {TicketTrelloService} from './TicketTrelloService'

export class TicketService {
    constructor() {
        this.repository = Repository.get('ticket')
        this.trelloService = new TicketTrelloService()
        this.ticket = this.trelloService.ticket
        this.categories = []
        this.openedList = []
        this.closedList = []
        this.openedPaginator = new Paginator(resources.ticket.list + '?status=0')
        this.closedPaginator = new Paginator(resources.ticket.list + '?status=1')

    }

    async updateList(data, type) {
        console.log('updatelist ticket', data, type)
        if (type === 'ticketListOpened')
            this.openedList = []
        else
            this.closedList = []

        const result  =  data?.data?.map((ticket) => {
            console.log('MAP', ticket)
            return {
                created: ticket.created_at,
                id: ticket.id,
                name: ticket.name,
                description: ticket.content,
                due: ticket.due,
                closed: ticket.status === 1,
                lastActivity: null,
                comments: ticket.comments,
                category: ticket.category.label_name,
                owner: ticket.owner.name + ticket.owner.surname,
                assigned: ticket.assigned_id &&  ticket.assigned_to? ticket.assigned_to.user_name : null,
                title: ticket.title,
            };
        });
        console.log("mapping resul", result);
        if (type === 'ticketListOpened')
            this.openedList = result;
        else
            this.closedList = result;

    }

    async getCategories() {
        try {
            let response = await this.repository.listCategory()
            if (response.status === 200 || response.status === 201) {
                this.categories = response.data.data
                return this.categories
            } else {

                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async createMaintenanceTicket(maintenanceData) {
        let maintenanceDataPM =
            {
                creator: maintenanceData.creator,
                dueDate: maintenanceData.dueDate,
                label: maintenanceData.category,
                outsourcing: maintenanceData.amount,
                description: maintenanceData.description,
                title: maintenanceData.title,
                owner_id: maintenanceData.assigned,
                owner_type: 'person',
                creator_type: 'admin'
            }
        try {
            let response = await this.repository.create(maintenanceDataPM)
            if (response.status === 200 || response.status === 201) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async closeTicket(id) {
        try {

            let response = await this.repository.close(id)

            if (response.status === 200 || response.status === 201) {
                this.ticket.closed = true
                return this.ticket
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {

            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

}
