import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class SidebarService {
    constructor() {
        this.repository = Repository.get('sidebar')
        this.sidebar = []
    }

    async list() {
        try {
            let response = await this.repository.list()

            if (response.status === 200) {
                this.sidebar = response.data.data
                return this.sidebar
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
