import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { Paginator } from '@/classes/paginator'
import { resources } from '@/resources'
import { TicketTrelloService } from './TicketTrelloService'

export class TicketService {
    constructor () {
        this.repository = Repository.get('ticket')
        this.trelloService = new TicketTrelloService()
        this.ticket = this.trelloService.ticket
        this.categories = []
        this.openedList = []
        this.closedList = []
        this.openedPaginator = new Paginator(resources.ticket.list + '?status=0')
        this.closedPaginator = new Paginator(resources.ticket.list + '?status=1')

    }

    async updateList (data, type) {
        if (type === 'ticketListOpened')
            this.openedList = []
        else
            this.closedList = []

        for (let m in data) {

            let ticketData = await this.trelloService.getTicketDetail(data[m])
            ticketData.assignedTo = data[m].assigned_to
            if (type === 'ticketListOpened') {

                if (ticketData)
                    this.openedList.push(ticketData)

            } else {

                if (ticketData) {

                    this.closedList.push(ticketData)
                }
            }
        }

    }

    async getCategories () {
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

    async createMaintenanceTicket (maintenanceData) {
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

    async closeTicket (id) {
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
