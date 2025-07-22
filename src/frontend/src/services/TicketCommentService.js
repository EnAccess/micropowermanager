import { ErrorHandler } from "@/Helpers/ErrorHandler"
import TicketCommentRepository from "@/repositories/TicketCommentRepository"

export class TicketCommentService {
  constructor() {
    this.repository = TicketCommentRepository
  }

  async createComment(comment, cardId, name, username) {
    try {
      let commentPm = {
        comment: comment,
        date: new Date(),
        fullName: name,
        username: username,
        cardId: cardId,
      }

      let response = await this.repository.create(commentPm)

      if (response.status === 200 || response.status === 201) {
        return commentPm
      } else {
        return new ErrorHandler(response.error, "http", response.status_code)
      }
    } catch (e) {
      console.log(e)
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
