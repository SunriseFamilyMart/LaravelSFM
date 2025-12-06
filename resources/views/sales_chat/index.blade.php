@extends('layouts.admin.app')

@section('title', 'Sales Chat')

@section('content')

    <style>
        .chat-wrapper {
            display: flex;
            height: 85vh;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.15);
        }

        .chat-list {
            width: 28%;
            background: #f4f7f9;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .chat-list-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            background: #fff;
            transition: 0.2s;
        }

        .chat-list-item:hover {
            background: #eaf3ff;
        }

        .chat-list-item.active {
            background: #d0ebff;
            font-weight: bold;
        }

        .chat-container {
            width: 72%;
            display: flex;
            flex-direction: column;
            background: #ece5dd;
        }

        .messages-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .chat-bubble {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 16px;
            margin-bottom: 15px;
            font-size: 15px;
            line-height: 1.4;
        }

        .received {
            margin-right: auto;
            background: #fff;
        }

        .sent {
            margin-left: auto;
            background: #dcf8c6;
        }

        .send-box {
            padding: 12px;
            background: #fff;
            border-top: 1px solid #ddd;
            display: flex;
        }

        .send-box input {
            border-radius: 20px;
            border: 1px solid #ccc;
            padding-left: 15px;
        }

        .send-box button {
            border-radius: 20px;
            margin-left: 10px;
            padding: 8px 20px;
        }
    </style>

    <div class="chat-wrapper">

        {{-- LEFT PANEL --}}
        <div class="chat-list">
            @foreach ($salesPeople as $item)
                <a href="?sales_person_id={{ $item->salesPerson->id }}">
                    <div class="chat-list-item {{ $selectedId == $item->salesPerson->id ? 'active' : '' }}">
                        {{ $item->salesPerson->name }}
                    </div>
                </a>
            @endforeach
        </div>

        {{-- CHAT WINDOW --}}
        <div class="chat-container">

            <div class="messages-area">
                @if (!$selectedId)
                    <p class="text-center mt-5">Select a Sales Person to start chat</p>
                @else
                    @foreach ($conversations as $chat)
                        {{-- Sales Person Message --}}
                        @if ($chat->message)
                            <div class="chat-bubble received">
                                <strong>{{ $chat->salesPerson->name }}</strong><br>
                                {{ $chat->message }}

                                {{-- IMAGES --}}
                                @php $images = json_decode($chat->image, true); @endphp
                                @if (is_array($images))
                                    @foreach ($images as $img)
                                        <img src="{{ $img }}" width="150" class="img-thumbnail mt-2">
                                    @endforeach
                                @endif
                            </div>
                        @endif

                        {{-- Admin Message --}}
                        @if ($chat->reply)
                            <div class="chat-bubble sent">
                                {{ $chat->reply }}
                            </div>
                        @endif
                    @endforeach

                @endif
            </div>

            {{-- SEND NEW MESSAGE --}}
            @if ($selectedId)
                <form action="{{ route('admin.message.sales.chats.reply') }}" method="POST" class="send-box">
                    @csrf
                    <input type="hidden" name="sales_person_id" value="{{ $selectedId }}">
                    <input type="text" name="reply" class="form-control" placeholder="Type a message..." required>
                    <button class="btn btn-success">Send</button>
                </form>
            @endif

        </div>

    </div>

@endsection
