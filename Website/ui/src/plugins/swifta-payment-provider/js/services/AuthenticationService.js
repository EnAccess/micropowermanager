import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class AuthenticationService {
    constructor () {
        this.repository = Repository.get('authentication')
        this.authentication = {
            id: null,
            token: null,

        }
    }

    fromJson (authenticationData) {
        this.authentication = {
            id: authenticationData.id,
            token: authenticationData.token,
        }
        return this.authentication
    }

    async getAuthentication () {
        try {
            let response = await this.repository.get()
            if (response.status === 200) {
                return this.fromJson(response.data.data)
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

}