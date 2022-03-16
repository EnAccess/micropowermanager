<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/tickets/api/ticket" id="ticketForm">
                <input type="hidden" name="creator" value="{{Auth::id()}}">
                <input type="hidden" name="owner_id" value="{{$person->id}}">
                <input type="hidden" name="owner_type" value="person">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">New Ticket</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="text" id="ticketTitle" name="ticketTitle" class="form-control"
                                       placeholder="Title" required/>
                            </div>
                            <div class="form-group">
                            <textarea class="form-control" placeholder="Description" rows="5" required
                                      id="ticketDescription" name="ticketDescription"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ticketPriority">Priority</label>
                                <select class="form-control" id="ticketPriority" name="ticketPriority">
                                    <option>Low</option>
                                    <option>Normal</option>
                                    <option>High</option>

                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category">Assign To</label>
                                <select class="form-control" id="ticketAssignedTo" name="ticketAssignedTo">
                                    <option disabled selected>No one</option>

                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12">

                            <div class="form-group">
                                <label for="ticketDueDate">Due Date</label>

                                <div class="input-group">
                                    <input type="text" name="ticketDueDate" id="ticketDueDate"
                                           placeholder="Due date DD.MM.YYYY"
                                           class="form-control hasDatepicker">
                                    <span class="input-group-btn">
                                <button class="btn btn-default" onclick="setToday()">
                                    <i class="fa fa-calendar"></i> Today
                                </button>
                              </span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Create Ticket
                    </button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
