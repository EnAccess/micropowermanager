import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class TicketCommentService {
    constructor () {
        this.repository = Repository.get('ticketComment')
    }

    async createComment (comment, cardId, name, username) {

        try {

            let commentPm = {
                comment: comment,
                date: new Date(),
                fullName: name,
                username: username,
                cardId: cardId
            }

            let response = await this.repository.create(commentPm)

            if (response.status === 200 || response.status === 201) {
                return commentPm
            } else {
                return new ErrorHandler(response.error, 'http', response.status_code)
            }
        } catch (e) {
            console.log(e)
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }
}
