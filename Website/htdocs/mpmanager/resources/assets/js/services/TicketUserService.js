import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class TicketUserService {
    constructor () {
        this.repository = Repository.get('ticketUser')
        this.list = []
        this.newUser = {
            name: '',
            tag: '',
            outsourcing: false,
        }
    }

    async getUsers () {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                let users = response.data.data
                this.list = users.map(this.pushUsers)
                return this.list
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }
    pushUsers(user){
        return {
            id: user.id,
            name: user.user_name,
            tag: user.user_tag,
            created_at:  user.created_at.toString().replace(/T/, ' ').replace(/\..+/, '')
        }
    }
    async createUser (name, tag, outsourcing) {
        try {
            let userPM = {
                'username': name,
                'usertag': tag,
                'outsourcing': outsourcing
            }

            let response = await this.repository.create(userPM)
            if (response.status === 200 || response.status === 201) {

                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

    resetNewUser () {
        this.newUser = {
            'name': '',
            'tag': '',
            'outsourcing': false,
        }
    }
}
