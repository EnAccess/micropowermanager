// Mirrored from src/frontend/src/Helpers/ErrorHandler.js so the service layer keeps
// the same throw contract across both applications.
export class ErrorHandler {
  constructor(_message, _type, _status_code) {
    this.exception = {
      message: _message,
      type: _type,
      status_code: _status_code,
    }
    this.throwException()
  }

  throwException() {
    throw this.exception
  }
}
