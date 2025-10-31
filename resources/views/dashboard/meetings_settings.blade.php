@extends('app')

@section('content')
<div class="container">
    <h1>Meeting Settings</h1>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('meeting.settings.update') }}">
        @csrf
        <div>
            <label>Select days for auto-creation:</label>
            @php
                $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                $selected = $settings->selected_days ?? ['Friday'];
            @endphp
            <div>
                @foreach($days as $day)
                    <label style="margin-right:10px">
                        <input type="checkbox" name="selected_days[]" value="{{ $day }}" {{ in_array($day, $selected) ? 'checked' : '' }}> {{ $day }}
                    </label>
                @endforeach
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save</button>
    </form>

    <form method="POST" action="{{ route('meeting.settings.toggle') }}" style="margin-top:10px">
        @csrf
        <button class="btn btn-secondary" type="submit">{{ ($settings->enabled ?? true) ? 'Stop Automatic Creation' : 'Start Automatic Creation' }}</button>
    </form>

    <form method="POST" action="{{ route('meeting.settings.runnow') }}" style="margin-top:10px">
        @csrf
        <button class="btn btn-success" type="submit">Run Now</button>
    </form>

    <h2 style="margin-top:20px">Meetings</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Start</th>
                <th>End</th>
                <th>Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach($meetings as $meeting)
            <tr>
                <td>{{ $meeting->id }}</td>
                <td>{{ $meeting->start_time }}</td>
                <td>{{ $meeting->end_time }}</td>
                <td>{{ $meeting->info }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

